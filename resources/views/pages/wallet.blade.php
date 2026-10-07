@extends('layouts.app')

@section('title', 'Quick PayMoney | My Wallet')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
@endsection

@section('styles')
    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/portal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/profile-dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/wallet.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">
        @include('partials.home-header')

        <main class="content-area app-shell profile-dashboard wallet-area">
            <h1 class="wallet-title">My Wallet</h1>

            @if (session('status'))
                <div class="portal-status" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->has('bank'))
                <div class="portal-error" role="alert">{{ $errors->first('bank') }}</div>
            @endif

            <section class="profile-balance-panel wallet-balances" aria-label="Wallet balances">
                <article class="profile-balance-card wallet-balance-card">
                    <span class="profile-balance-icon"><i class="bi bi-currency-dollar" aria-hidden="true"></i></span>
                    <span class="profile-balance-label">USD Balance</span>
                    <strong>{{ \App\Support\Money::formatUsd((string) $user->balance) }}</strong>
                </article>
                <article class="profile-balance-card wallet-balance-card wallet-inr-card">
                    <span class="profile-balance-icon"><i class="bi bi-currency-rupee" aria-hidden="true"></i></span>
                    <span class="profile-balance-label">INR Balance</span>
                    <strong>{{ \App\Support\Money::formatInr((string) $user->inr_balance) }}</strong>
                </article>
            </section>

            <div class="profile-primary-actions wallet-primary-actions">
                <a class="profile-action-button profile-bank-action" href="{{ route('deposit.create') }}">
                    <i class="bi bi-plus-circle" aria-hidden="true"></i><span>Add Funds</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                </a>
                @if (\App\Support\Decimal::compare((string) $user->inr_balance, '0', 2) > 0 && $hasBankDetails)
                    <a class="profile-action-button profile-sell-action wallet-withdraw-action" href="#withdrawal-form">
                        <i class="bi bi-bank" aria-hidden="true"></i><span>Withdraw INR</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </a>
                @else
                    <button class="profile-action-button wallet-disabled-action" type="button" disabled>
                        <i class="bi bi-bank" aria-hidden="true"></i><span>Withdraw INR</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                    </button>
                @endif
            </div>

            @if (\App\Support\Decimal::compare((string) $user->inr_balance, '0', 2) <= 0)
                <p class="wallet-inline-notice">No INR balance available for withdrawal.</p>
            @elseif (! $hasBankDetails)
                <p class="wallet-inline-notice">Add complete bank details in <a href="{{ route('profile.bank') }}">Profile</a> before withdrawing.</p>
            @endif

            <section class="portal-card wallet-history-card" id="deposit-history" aria-labelledby="deposit-history-title">
                <div class="wallet-section-heading">
                    <h2 id="deposit-history-title"><i class="bi bi-arrow-down-left-circle" aria-hidden="true"></i> Deposit History</h2>
                </div>
                <form class="wallet-filters" method="GET" action="{{ route('wallet') }}" role="search">
                    <label class="visually-hidden" for="wallet-search">Search deposit reference or transaction ID</label>
                    <input class="portal-input" id="wallet-search" type="search" name="search" value="{{ request('search') }}" maxlength="150" placeholder="Search reference or transaction ID">
                    <label class="visually-hidden" for="wallet-deposit-status">Filter deposits by status</label>
                    <select class="portal-input" id="wallet-deposit-status" name="status">
                        <option value="">All deposit statuses</option>
                        @foreach (\App\Models\Deposit::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    @if (request()->filled('withdrawal_status'))
                        <input type="hidden" name="withdrawal_status" value="{{ request('withdrawal_status') }}">
                    @endif
                    <button class="wallet-filter-button" type="submit"><i class="bi bi-search" aria-hidden="true"></i><span>Filter</span></button>
                </form>

                @forelse ($deposits as $deposit)
                    <article class="wallet-history-item">
                        <div class="wallet-history-main">
                            <div><span class="wallet-history-label">Reference ID</span><strong>{{ $deposit->deposit_id }}</strong></div>
                            <div><span class="wallet-history-label">Amount (USD)</span><strong>{{ \App\Support\Money::formatUsd((string) $deposit->amount) }}</strong></div>
                            <div><span class="wallet-history-label">Transaction ID</span><strong class="wallet-history-wrap">{{ $deposit->transaction_reference }}</strong></div>
                            <div><span class="wallet-history-label">Date</span><strong>{{ $deposit->submitted_at->format('M j, Y · H:i') }}</strong></div>
                        </div>
                        <div class="wallet-history-footer">
                            <span class="portal-badge {{ $deposit->status }}">{{ ucfirst($deposit->status) }}</span>
                            <a class="wallet-details-link" href="{{ route('deposits.show', $deposit) }}">Details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                        </div>
                        @if ($deposit->status === 'rejected' && $deposit->rejection_reason)
                            <p class="wallet-history-note">{{ $deposit->rejection_reason }}</p>
                        @endif
                    </article>
                @empty
                    <p class="wallet-empty">{{ request()->filled('search') || request()->filled('status') ? 'No matching deposits.' : 'No deposits yet.' }}</p>
                @endforelse
                @if ($deposits->hasPages())
                    <div class="wallet-pagination">{{ $deposits->links() }}</div>
                @endif
            </section>

            <section class="portal-card wallet-history-card" id="withdrawal-history" aria-labelledby="withdrawal-history-title">
                <div class="wallet-section-heading">
                    <h2 id="withdrawal-history-title"><i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i> Withdrawal History</h2>
                </div>
                <form class="wallet-filters wallet-withdrawal-filter" method="GET" action="{{ route('wallet') }}">
                    @if (request()->filled('search'))<input type="hidden" name="search" value="{{ request('search') }}">@endif
                    @if (request()->filled('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
                    <label class="visually-hidden" for="wallet-withdrawal-status">Filter withdrawals by status</label>
                    <select class="portal-input" id="wallet-withdrawal-status" name="withdrawal_status">
                        <option value="">All withdrawal statuses</option>
                        @foreach (\App\Models\WithdrawalRequest::STATUSES as $status)
                            <option value="{{ $status }}" @selected(request('withdrawal_status') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                    <button class="wallet-filter-button" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i><span>Filter</span></button>
                </form>

                @if ($hasBankDetails && \App\Support\Decimal::compare((string) $user->inr_balance, '0', 2) > 0)
                    <form class="wallet-withdrawal-form" id="withdrawal-form" method="POST" action="{{ route('wallet.withdrawals.store') }}">
                        @csrf
                        <input type="hidden" name="submission_key" value="{{ $withdrawalSubmissionKey }}">
                        <label for="withdrawal-amount">Withdrawal Amount (INR)</label>
                        <input class="portal-input" id="withdrawal-amount" name="amount" type="number" min="0.01" max="{{ $user->inr_balance }}" step="0.01" inputmode="decimal" value="{{ old('amount') }}" required>
                        @error('amount')<span class="wallet-field-error">{{ $message }}</span>@enderror
                        <div class="wallet-bank-destination">
                            <i class="bi bi-bank" aria-hidden="true"></i>
                            <span>{{ $user->bank_name }} · {{ $user->account_holder_name }} · ending {{ substr((string) $user->account_number, -4) }}</span>
                        </div>
                        <button class="profile-action-button profile-sell-action wallet-withdraw-submit" type="submit">
                            <i class="bi bi-arrow-up-right-circle" aria-hidden="true"></i><span>Request withdrawal</span><i class="bi bi-arrow-right" aria-hidden="true"></i>
                        </button>
                    </form>
                @endif

                @forelse ($withdrawals as $withdrawal)
                    <article class="wallet-history-item">
                        <div class="wallet-history-main">
                            <div><span class="wallet-history-label">Reference ID</span><strong>{{ $withdrawal->request_reference }}</strong></div>
                            <div><span class="wallet-history-label">Amount (INR)</span><strong>{{ \App\Support\Money::formatInr((string) $withdrawal->amount) }}</strong></div>
                            <div><span class="wallet-history-label">Bank</span><strong>{{ $withdrawal->bank_name }} · ending {{ substr((string) $withdrawal->bank_account_number, -4) }}</strong></div>
                            <div><span class="wallet-history-label">Requested Date</span><strong>{{ $withdrawal->requested_at->format('M j, Y · H:i') }}</strong></div>
                        </div>
                        <div class="wallet-history-footer">
                            <span class="portal-badge {{ $withdrawal->status }}">{{ ucfirst($withdrawal->status) }}</span>
                            <a class="wallet-details-link" href="{{ route('wallet.withdrawals.show', $withdrawal) }}">Details <i class="bi bi-arrow-up-right" aria-hidden="true"></i></a>
                        </div>
                    </article>
                @empty
                    <p class="wallet-empty">{{ request()->filled('withdrawal_status') ? 'No matching withdrawals.' : 'No withdrawal requests yet.' }}</p>
                @endforelse
                @if ($withdrawals->hasPages())
                    <div class="wallet-pagination">{{ $withdrawals->links() }}</div>
                @endif
            </section>
        </main>

        @include('partials.bottom-nav', ['active' => 'wallet', 'variant' => 'wallet', 'appShell' => true])
    </div>
@endsection
