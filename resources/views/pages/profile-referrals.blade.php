@extends('layouts.app')

@section('title', 'Quick PayMoney | Referrals')

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
            <a class="profile-back-link" href="{{ route('profile') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to Profile</a>
            <section class="profile-content-card">
                <div class="profile-content-heading"><span class="profile-content-icon"><i class="bi bi-people" aria-hidden="true"></i></span><div><h2>Referrals</h2><p>Invite customers with your personal referral link.</p></div></div>
                <div class="profile-referral-stats">
                    <article><span>Total Referrals</span><strong>{{ number_format($referralCount) }}</strong></article>
                    <article><span>Active Referrals</span><strong>{{ number_format($activeReferralCount) }}</strong></article>
                </div>
                <div class="profile-referral-field">
                    <label for="referral-code">My Referral Code</label>
                    <div class="profile-copy-field"><input class="profile-form-control" id="referral-code" value="{{ $user->referral_code }}" readonly><button type="button" data-copy-value="{{ $user->referral_code }}" aria-label="Copy referral code"><i class="bi bi-copy" aria-hidden="true"></i><span data-copy-feedback aria-live="polite"></span></button></div>
                </div>
                <div class="profile-referral-field">
                    <label for="referral-link">Referral Link</label>
                    <div class="profile-copy-field"><input class="profile-form-control" id="referral-link" value="{{ route('register', ['ref' => $user->referral_code]) }}" readonly><button type="button" data-copy-value="{{ route('register', ['ref' => $user->referral_code]) }}" aria-label="Copy referral link"><i class="bi bi-share" aria-hidden="true"></i><span data-copy-feedback aria-live="polite"></span></button></div>
                </div>
                <p class="profile-balance-disclaimer">Referral rewards are not currently supported. No reward amount is accrued or displayed.</p>
            </section>
        </main>
        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection

@section('scripts')
    @include('partials.profile-copy-script')
@endsection
