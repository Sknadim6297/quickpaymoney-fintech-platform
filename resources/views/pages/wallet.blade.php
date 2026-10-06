@extends('layouts.app')

@section('title', 'Quick PayMoney | My Wallet')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
@endsection

@section('styles')
    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/portal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/deposit.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/wallet.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">
        @include('partials.home-header')

        <main class="content-area wallet-area">
            <div class="wallet-page-heading">
                <div><span class="wallet-eyebrow">CUSTOMER PANEL</span><h1 class="portal-title">My Wallet</h1><p class="portal-muted">Review your internal recorded balance and deposit activity.</p></div>
                <a class="wallet-deposit-link" href="{{ route('deposit.create') }}"><i class="bi bi-plus-circle" aria-hidden="true"></i> Add Funds</a>
            </div>

            <section class="wallet-summary-grid" aria-label="Wallet summary">
                <article class="wallet-summary-card">
                    <span class="wallet-summary-icon"><i class="bi bi-currency-dollar" aria-hidden="true"></i></span>
                    <div><span>Total Recorded USD Balance</span><strong>{{ \App\Support\Money::formatUsd($user->balance) }}</strong></div>
                </article>
                <article class="wallet-summary-card">
                    <span class="wallet-summary-icon"><i class="bi bi-currency-exchange" aria-hidden="true"></i></span>
                    <div><span>Estimated INR Equivalent</span><strong>{{ $inrEquivalent !== null ? \App\Support\Money::formatInr($inrEquivalent) : 'Rate unavailable' }}</strong><small>At current Base Rate reference</small></div>
                </article>
                <article class="wallet-summary-card">
                    <span class="wallet-summary-icon"><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i></span>
                    <div><span>Total Withdrawn (USD)</span><strong>{{ $withdrawalsTracked ? \App\Support\Money::formatUsd('0.00') : 'Not tracked' }}</strong><small>No withdrawal records exist in this system.</small></div>
                </article>
                <article class="wallet-summary-card">
                    <span class="wallet-summary-icon"><i class="bi bi-cash-stack" aria-hidden="true"></i></span>
                    <div><span>Total Withdrawn (INR)</span><strong>{{ $withdrawalsTracked ? \App\Support\Money::formatInr('0.00') : 'Not tracked' }}</strong><small>No completed withdrawal payouts recorded.</small></div>
                </article>
                <article class="wallet-summary-card">
                    <span class="wallet-summary-icon"><i class="bi bi-wallet" aria-hidden="true"></i></span>
                    <div><span>Total Deposits</span><strong>{{ \App\Support\Money::formatUsd($totalDeposited) }}</strong><small>{{ $completedDepositCount }} approved {{ \Illuminate\Support\Str::plural('deposit', $completedDepositCount) }}</small></div>
                </article>
                <article class="wallet-summary-card wallet-available-card">
                    <span class="wallet-summary-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span>
                    <div><span>Available Recorded Balance</span><strong>{{ \App\Support\Money::formatUsd($user->balance) }}</strong><small>Withdrawals are not currently supported.</small></div>
                </article>
            </section>

            <p class="wallet-disclaimer"><i class="bi bi-info-circle" aria-hidden="true"></i> Balance is an internal recorded amount, not a custodial wallet or confirmed USDT holdings. INR is an estimate based on the current reference rate, before verification or applicable fees.</p>

            <section class="portal-card wallet-history-card" id="deposit-history">
                <div class="wallet-section-heading"><div><span class="wallet-eyebrow">ACCOUNT ACTIVITY</span><h2><i class="bi bi-arrow-down-left-circle" aria-hidden="true"></i> Deposit History</h2><p>Pending and rejected deposits are shown here but do not count toward completed deposit totals.</p></div></div>

                <form class="wallet-filters" method="GET" action="{{ route('wallet') }}" role="search">
                    <div>
                        <label class="visually-hidden" for="wallet-search">Search Reference ID or Transaction ID</label>
                        <input class="portal-input" id="wallet-search" type="search" name="search" value="{{ request('search') }}" maxlength="150" placeholder="Search Reference ID or Transaction ID">
                    </div>
                    <div>
                        <label class="visually-hidden" for="wallet-status">Filter by deposit status</label>
                        <select class="portal-input" id="wallet-status" name="status">
                            <option value="">All statuses</option>
                            @foreach (\App\Models\Deposit::STATUSES as $status)
                                <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button class="wallet-filter-button" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Search</button>
                    @if (request()->hasAny(['search', 'status']))
                        <a class="wallet-filter-button secondary" href="{{ route('wallet') }}">Clear</a>
                    @endif
                </form>

                @if ($deposits->isEmpty())
                    <div class="deposit-history-empty"><i class="bi bi-receipt" aria-hidden="true"></i><strong>{{ request()->hasAny(['search', 'status']) ? 'No matching deposits' : 'No deposits yet' }}</strong><p>{{ request()->hasAny(['search', 'status']) ? 'Change or clear the filters to see more records.' : 'Your deposit requests will appear here.' }}</p></div>
                @else
                    <div class="portal-table-wrap wallet-table-wrap">
                        <table class="portal-table">
                            <thead><tr><th scope="col">Reference ID</th><th scope="col">Amount (USD)</th><th scope="col">Transaction ID</th><th scope="col">Submitted Date</th><th scope="col">Status</th><th scope="col">Rejection Reason</th><th scope="col">Details</th></tr></thead>
                            <tbody>
                                @foreach ($deposits as $deposit)
                                    <tr>
                                        <td>{{ $deposit->deposit_id }}</td>
                                        <td>{{ \App\Support\Money::formatUsd($deposit->amount) }}</td>
                                        <td>{{ $deposit->transaction_reference }}</td>
                                        <td><time datetime="{{ $deposit->submitted_at->toIso8601String() }}">{{ $deposit->submitted_at->format('M j, Y · H:i') }}</time></td>
                                        <td><span class="portal-badge {{ $deposit->status }}">{{ ucfirst($deposit->status) }}</span></td>
                                        <td>{{ $deposit->rejection_reason ?: '—' }}</td>
                                        <td><a class="wallet-details-link" href="{{ route('deposits.show', $deposit) }}">View details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($deposits->hasPages())<div class="deposit-history-pagination">{{ $deposits->links() }}</div>@endif
                @endif
            </section>

            <section class="portal-card wallet-history-card" id="withdrawal-history">
                <div class="wallet-section-heading"><div><span class="wallet-eyebrow">ACCOUNT ACTIVITY</span><h2><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i> Withdrawal History</h2><p>Completed payout references and statuses will appear here if a withdrawal workflow is added.</p></div></div>
                <div class="deposit-history-empty"><i class="bi bi-clock-history" aria-hidden="true"></i><strong>Withdrawal history is not available</strong><p>There is no withdrawal request or payout workflow in this system. No transfers have been initiated.</p></div>
            </section>
        </main>

        @include('partials.bottom-nav', ['active' => 'wallet', 'variant' => 'wallet'])
    </div>
@endsection
