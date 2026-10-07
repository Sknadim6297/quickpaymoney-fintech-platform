@extends('layouts.app')

@section('title', 'Quick PayMoney | Referral History')

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
                <div class="profile-content-heading"><span class="profile-content-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span><div><h2>Referrals History</h2><p>Customers who joined using your referral link.</p></div></div>
                @if ($referrals->isEmpty())
                    <div class="profile-empty-state"><i class="bi bi-people" aria-hidden="true"></i><strong>No referrals yet</strong><p>Customers who register through your link will be listed here.</p></div>
                @else
                    <div class="profile-table-wrap">
                        <table class="profile-data-table">
                            <thead><tr><th>Customer</th><th>Customer ID</th><th>Joined</th><th>Status</th></tr></thead>
                            <tbody>@foreach ($referrals as $referral)
                                <tr><td>Referred customer</td><td>#{{ $referral->id }}</td><td><time datetime="{{ $referral->created_at->toIso8601String() }}">{{ $referral->created_at->format('M j, Y') }}</time></td><td><span class="portal-badge {{ $referral->account_status === 'active' ? 'active' : 'suspended' }}">{{ ucfirst($referral->account_status) }}</span></td></tr>
                            @endforeach</tbody>
                        </table>
                    </div>
                    @if ($referrals->hasPages())<div class="profile-pagination">{{ $referrals->links() }}</div>@endif
                @endif
                <p class="profile-balance-disclaimer">Referral rewards are not currently supported. No financial reward history is recorded.</p>
            </section>
        </main>
        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection

@section('scripts')
    @include('partials.profile-copy-script')
@endsection
