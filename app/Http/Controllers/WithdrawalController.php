<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InrLedgerEntry;
use App\Models\User;
use App\Models\WithdrawalRequest;
use App\Support\Decimal;
use App\Support\Money;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class WithdrawalController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'submission_key' => ['required', 'uuid'],
            'amount' => ['required', 'string', 'regex:/^(?:0|[1-9]\d{0,11})(?:\.\d{1,2})?$/'],
        ]);
        $user = $request->user('web');
        abort_unless($user?->role === 'user', 403);

        if (Decimal::compare($validated['amount'], '0', 2) <= 0) {
            throw ValidationException::withMessages(['amount' => 'Enter a withdrawal amount greater than zero.']);
        }
        $amount = Money::normalizeUsd($validated['amount']);

        $existing = WithdrawalRequest::query()
            ->where('user_id', $user->id)
            ->where('submission_key', $validated['submission_key'])
            ->first();
        if ($existing) {
            return $this->submitted($existing);
        }

        try {
            $withdrawal = DB::transaction(function () use ($request, $user, $validated, $amount): WithdrawalRequest {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $existing = WithdrawalRequest::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('submission_key', $validated['submission_key'])
                    ->first();
                if ($existing) {
                    return $existing;
                }

                $bankStatus = $lockedUser->bankVerificationStatus();
                if ($bankStatus !== 'verified') {
                    $message = match ($bankStatus) {
                        'pending' => 'Your bank details are pending verification.',
                        'rejected' => 'Your bank details were not verified. Please update them and submit again.',
                        default => 'Add your bank details before withdrawing.',
                    };

                    throw ValidationException::withMessages(['bank' => $message]);
                }

                if (Decimal::compare((string) $lockedUser->inr_balance, $amount, 2) < 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'The withdrawal amount exceeds your available INR balance.',
                    ]);
                }

                $withdrawal = WithdrawalRequest::create([
                    'request_reference' => $this->requestReference(),
                    'user_id' => $lockedUser->id,
                    'submission_key' => $validated['submission_key'],
                    'amount' => $amount,
                    'bank_account_holder' => $lockedUser->account_holder_name,
                    'bank_name' => $lockedUser->bank_name,
                    'bank_account_number' => $lockedUser->account_number,
                    'bank_ifsc_code' => $lockedUser->ifsc_code,
                    'bank_branch_name' => $lockedUser->branch_name,
                    'bank_account_type' => $lockedUser->account_type,
                    'status' => 'pending',
                    'requested_at' => now(),
                ]);

                InrLedgerEntry::create([
                    'user_id' => $lockedUser->id,
                    'actor_user_id' => $lockedUser->id,
                    'source_type' => 'withdrawal_request',
                    'source_id' => $withdrawal->id,
                    'entry_type' => 'debit',
                    'amount' => $amount,
                ]);
                DB::table('users')->where('id', $lockedUser->id)->decrement('inr_balance', $amount);

                AuditLog::create([
                    'actor_user_id' => $lockedUser->id,
                    'subject_user_id' => $lockedUser->id,
                    'event' => 'withdrawal.submitted',
                    'auditable_type' => WithdrawalRequest::class,
                    'auditable_id' => $withdrawal->id,
                    'metadata' => [
                        'request_reference' => $withdrawal->request_reference,
                        'amount' => $amount,
                        'bank_name' => $lockedUser->bank_name,
                    ],
                    'ip_address' => $request->ip(),
                ]);

                return $withdrawal;
            });
        } catch (QueryException $exception) {
            $withdrawal = WithdrawalRequest::query()
                ->where('user_id', $user->id)
                ->where('submission_key', $validated['submission_key'])
                ->first();
            if (! $withdrawal) {
                throw $exception;
            }
        }

        return $this->submitted($withdrawal);
    }

    public function show(Request $request, WithdrawalRequest $withdrawalRequest): View
    {
        abort_unless($withdrawalRequest->user_id === $request->user('web')?->id, 404);

        return view('pages.withdrawal.show', ['withdrawal' => $withdrawalRequest]);
    }

    private function requestReference(): string
    {
        do {
            $reference = 'WDR-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
        } while (WithdrawalRequest::query()->where('request_reference', $reference)->exists());

        return $reference;
    }

    private function submitted(WithdrawalRequest $withdrawal): RedirectResponse
    {
        $message = match ($withdrawal->status) {
            'completed' => 'Withdrawal '.$withdrawal->request_reference.' is marked completed with payout reference '.$withdrawal->transaction_reference.'. The system did not initiate a bank transfer.',
            'rejected' => 'Withdrawal '.$withdrawal->request_reference.' was rejected and the reserved INR was returned to your balance.',
            'processing' => 'Withdrawal '.$withdrawal->request_reference.' is being processed. A bank transfer is not automated by this system.',
            default => 'Withdrawal request '.$withdrawal->request_reference.' was submitted for review. No bank transfer has been initiated.',
        };

        return redirect()->to(route('wallet').'#withdrawal-history')
            ->with('status', $message);
    }
}
