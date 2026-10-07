<?php

namespace App\Http\Controllers;

use App\Models\Deposit;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user('web');
        abort_unless($user?->role === 'user', 403);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', Rule::in(Deposit::STATUSES)],
            'withdrawal_status' => ['nullable', Rule::in(WithdrawalRequest::STATUSES)],
        ]);

        $deposits = $user->deposits()
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('deposit_id', 'like', '%'.$search.'%')
                        ->orWhere('transaction_reference', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('submitted_at')
            ->paginate(10)
            ->withQueryString();

        $withdrawals = $user->withdrawalRequests()
            ->when($filters['withdrawal_status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest('requested_at')
            ->paginate(10, ['*'], 'withdrawal_page')
            ->withQueryString();
        $hasBankDetails = filled($user->account_holder_name)
            && filled($user->bank_name)
            && filled($user->account_number)
            && filled($user->ifsc_code);

        return view('pages.wallet', [
            'user' => $user,
            'deposits' => $deposits,
            'withdrawals' => $withdrawals,
            'hasBankDetails' => $hasBankDetails,
            'withdrawalSubmissionKey' => (string) Str::uuid(),
        ]);
    }
}
