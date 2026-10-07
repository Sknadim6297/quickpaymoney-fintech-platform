@extends('layouts.app')

@section('title', 'Quick PayMoney | Exchange Details')

@section('font')
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
@endsection

@section('styles')
    <link href="{{ asset('assets/css/index.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/portal.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/profile-dashboard.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/style.css') }}" rel="stylesheet">
@endsection

@section('content')
    <div class="main-wrapper">
        @include('partials.home-header')
        <main class="content-area profile-dashboard profile-subpage">
            @include('partials.profile-hero', ['user' => $user])
            <a class="profile-back-link" href="{{ route('profile.exchanges') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Exchange History</a>
            <section class="profile-content-card">
                <div class="profile-content-heading"><span class="profile-content-icon"><i class="bi bi-receipt" aria-hidden="true"></i></span><div><h2>Exchange Details</h2><p>Request reference {{ $exchange->request_reference ?: 'EXC-'.$exchange->id }}</p></div></div>
                @php
                    $displayAmount = rtrim(rtrim((string) $exchange->usdt_amount, '0'), '.');
                    $displayRate = rtrim(rtrim((string) $exchange->exchange_rate, '0'), '.');
                @endphp
                <dl class="profile-detail-list">
                    <div><dt>USDT Amount</dt><dd>{{ $displayAmount }} USDT</dd></div>
                    <div><dt>Applied INR Rate</dt><dd>₹{{ $displayRate }} per USDT</dd></div>
                    <div><dt>Recorded INR Amount</dt><dd>{{ \App\Support\Money::formatInr((string) $exchange->inr_amount) }}</dd></div>
                    <div><dt>Status</dt><dd><span class="portal-badge {{ $exchange->status }}">{{ ucfirst($exchange->status) }}</span></dd></div>
                    @if ($exchange->transaction_reference)<div><dt>Verification reference</dt><dd>{{ $exchange->transaction_reference }}</dd></div>@endif
                    <div><dt>Submitted</dt><dd><time datetime="{{ $exchange->created_at->toIso8601String() }}">{{ $exchange->created_at->format('M j, Y · H:i') }}</time></dd></div>
                </dl>
                <p class="profile-balance-disclaimer">Amounts and rates shown are the historical values recorded with this request; they are not recalculated using current rates.</p>
            </section>
        </main>
        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection

@section('scripts')
    @include('partials.profile-copy-script')
@endsection
