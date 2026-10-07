@extends('layouts.app')

@section('title', 'Quick PayMoney | Withdrawal Details')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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
            <h1 class="wallet-title">Withdrawal Details</h1>
            <section class="portal-card wallet-history-card">
                <div class="wallet-history-main">
                    <div><span class="wallet-history-label">Reference ID</span><strong>{{ $withdrawal->request_reference }}</strong></div>
                    <div><span class="wallet-history-label">Amount (INR)</span><strong>{{ \App\Support\Money::formatInr((string) $withdrawal->amount) }}</strong></div>
                    <div><span class="wallet-history-label">Status</span><strong><span class="portal-badge {{ $withdrawal->status }}">{{ ucfirst($withdrawal->status) }}</span></strong></div>
                    <div><span class="wallet-history-label">Requested</span><strong>{{ $withdrawal->requested_at->format('M j, Y · H:i') }}</strong></div>
                    <div><span class="wallet-history-label">Bank</span><strong>{{ $withdrawal->bank_name }}</strong></div>
                    <div><span class="wallet-history-label">Account</span><strong>{{ $withdrawal->bank_account_holder }} · ending {{ substr((string) $withdrawal->bank_account_number, -4) }}</strong></div>
                    @if ($withdrawal->transaction_reference)
                        <div><span class="wallet-history-label">Payout reference</span><strong>{{ $withdrawal->transaction_reference }}</strong></div>
                    @endif
                </div>
                <p class="wallet-inline-notice">A request or approval does not itself transfer funds. Any payout is handled separately by an administrator.</p>
                <a class="profile-action-button profile-bank-action wallet-back-link" href="{{ route('wallet') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i><span>Back to My Wallet</span><i class="bi bi-arrow-right" aria-hidden="true"></i></a>
            </section>
        </main>
        @include('partials.bottom-nav', ['active' => 'wallet', 'variant' => 'wallet', 'appShell' => true])
    </div>
@endsection
