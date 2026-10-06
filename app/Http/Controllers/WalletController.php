<?php

namespace App\Http\Controllers;

use App\Models\BalanceLedgerEntry;
use App\Models\Deposit;
use App\Models\ExchangeRate;
use App\Support\Decimal;
use Illuminate\Http\Request;
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
        ]);

        $ledger = BalanceLedgerEntry::query()
            ->where('user_id', $user->id)
            ->where('entry_type', 'credit')
            ->whereHas('deposit', fn ($query) => $query->where('status', 'approved'));
        $totalDeposited = (string) ($ledger->sum('amount') ?: '0.00');
        $completedDepositCount = (clone $ledger)->count();

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

        $baseRate = ExchangeRate::query()
            ->where('plan_key', 'base')
            ->where('is_active', true)
            ->first();
        $inrEquivalent = $baseRate
            ? Decimal::multiplyToCents((string) $user->balance, (string) $baseRate->rate)
            : null;

        return view('pages.wallet', [
            'user' => $user,
            'deposits' => $deposits,
            'totalDeposited' => $totalDeposited,
            'completedDepositCount' => $completedDepositCount,
            'inrEquivalent' => $inrEquivalent,
            'withdrawalsTracked' => false,
        ]);
    }
}
