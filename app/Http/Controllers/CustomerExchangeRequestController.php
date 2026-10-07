<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExchangeRate;
use App\Models\ExchangeRequest;
use App\Models\User;
use App\Support\Decimal;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CustomerExchangeRequestController extends Controller
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
            throw ValidationException::withMessages(['amount' => 'Enter a USDT amount greater than zero.']);
        }

        $existing = ExchangeRequest::query()
            ->where('user_id', $user->id)
            ->where('submission_key', $validated['submission_key'])
            ->first();
        if ($existing) {
            return $this->submitted($existing);
        }

        try {
            $exchange = DB::transaction(function () use ($request, $user, $validated): ExchangeRequest {
                $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();
                $existing = ExchangeRequest::query()
                    ->where('user_id', $lockedUser->id)
                    ->where('submission_key', $validated['submission_key'])
                    ->first();
                if ($existing) {
                    return $existing;
                }

                if (Decimal::compare((string) $lockedUser->balance, $validated['amount'], 2) < 0) {
                    throw ValidationException::withMessages(['amount' => 'The requested amount exceeds your available USD balance.']);
                }

                $plan = ExchangeRate::query()
                    ->where('is_active', true)
                    ->where('minimum_amount', '<=', $validated['amount'])
                    ->where(fn ($query) => $query->whereNull('maximum_amount')->orWhere('maximum_amount', '>=', $validated['amount']))
                    ->orderByDesc('minimum_amount')
                    ->first();
                if (! $plan) {
                    throw ValidationException::withMessages(['amount' => 'No active rate plan matches this amount. Please contact support.']);
                }

                $exchange = ExchangeRequest::create([
                    'user_id' => $lockedUser->id,
                    'request_reference' => $this->requestReference(),
                    'submission_key' => $validated['submission_key'],
                    'rate_plan_name' => $plan->name,
                    'usdt_amount' => $validated['amount'],
                    'exchange_rate' => (string) $plan->rate,
                    'inr_amount' => Decimal::multiplyToCents($validated['amount'], (string) $plan->rate),
                    'status' => 'pending',
                ]);

                AuditLog::create([
                    'actor_user_id' => $lockedUser->id,
                    'subject_user_id' => $lockedUser->id,
                    'event' => 'exchange.submitted',
                    'auditable_type' => ExchangeRequest::class,
                    'auditable_id' => $exchange->id,
                    'metadata' => [
                        'request_reference' => $exchange->request_reference,
                        'usdt_amount' => $exchange->usdt_amount,
                        'exchange_rate' => $exchange->exchange_rate,
                        'inr_amount' => $exchange->inr_amount,
                    ],
                    'ip_address' => $request->ip(),
                ]);

                return $exchange;
            });
        } catch (QueryException $exception) {
            $exchange = ExchangeRequest::query()
                ->where('user_id', $user->id)
                ->where('submission_key', $validated['submission_key'])
                ->first();
            if (! $exchange) {
                throw $exception;
            }
        }

        return $this->submitted($exchange);
    }

    private function requestReference(): string
    {
        do {
            $reference = 'EXC-'.now()->format('Ymd').'-'.Str::upper(Str::random(10));
        } while (ExchangeRequest::query()->where('request_reference', $reference)->exists());

        return $reference;
    }

    private function submitted(ExchangeRequest $exchange): RedirectResponse
    {
        $message = match ($exchange->status) {
            'completed' => 'Sell request '.$exchange->request_reference.' completed and its saved INR amount was credited to your wallet.',
            'rejected' => 'Sell request '.$exchange->request_reference.' was rejected. Your USD balance was not changed.',
            'processing' => 'Sell request '.$exchange->request_reference.' is being reviewed. Your USD balance remains unchanged until completion.',
            default => 'Sell request '.$exchange->request_reference.' was submitted. Your USD balance will only change if an admin completes the exchange.',
        };

        return redirect()->route('profile.exchanges')
            ->with('status', $message);
    }
}
