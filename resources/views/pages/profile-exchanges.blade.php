@extends('layouts.app')

@section('title', 'Quick PayMoney | Exchange History')

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
                <div class="profile-content-heading"><span class="profile-content-icon"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></span><div><h2>Exchange History</h2><p>Your exchange requests and their original rate and payout snapshots.</p></div></div>
                <form class="profile-filter-form" method="GET" action="{{ route('profile.exchanges') }}">
                    <label class="visually-hidden" for="exchange-search">Search reference ID</label>
                    <input class="profile-form-control" id="exchange-search" type="search" name="search" maxlength="120" value="{{ request('search') }}" placeholder="Search reference ID">
                    <label class="visually-hidden" for="exchange-status">Filter by status</label>
                    <select class="profile-form-control" id="exchange-status" name="status"><option value="">All statuses</option>@foreach (\App\Models\ExchangeRequest::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                    <button class="profile-filter-button" type="submit"><i class="bi bi-search" aria-hidden="true"></i> Filter</button>
                    @if (request()->hasAny(['search', 'status']))<a class="profile-clear-filter" href="{{ route('profile.exchanges') }}">Clear</a>@endif
                </form>
                @if ($exchanges->isEmpty())
                    <div class="profile-empty-state"><i class="bi bi-receipt" aria-hidden="true"></i><strong>No exchange requests yet</strong><p>Your submitted exchange requests will appear here.</p></div>
                @else
                    <div class="profile-table-wrap">
                        <table class="profile-data-table">
                            <thead><tr><th>Reference ID</th><th>USDT</th><th>Rate</th><th>INR Amount</th><th>Status</th><th>Created</th><th>Details</th></tr></thead>
                            <tbody>@foreach ($exchanges as $exchange)
                                @php
                                    $displayAmount = rtrim(rtrim((string) $exchange->usdt_amount, '0'), '.');
                                    $displayRate = rtrim(rtrim((string) $exchange->exchange_rate, '0'), '.');
                                @endphp
                                <tr>
                                    <td>{{ $exchange->request_reference ?: ($exchange->transaction_reference ?: 'EXC-'.$exchange->id) }}</td>
                                    <td>{{ $displayAmount }} USDT</td>
                                    <td>₹{{ $displayRate }}</td>
                                    <td>{{ \App\Support\Money::formatInr((string) $exchange->inr_amount) }}</td>
                                    <td><span class="portal-badge {{ $exchange->status }}">{{ ucfirst($exchange->status) }}</span></td>
                                    <td><time datetime="{{ $exchange->created_at->toIso8601String() }}">{{ $exchange->created_at->format('M j, Y H:i') }}</time></td>
                                    <td><a class="profile-view-link" href="{{ route('profile.exchanges.show', $exchange) }}">View</a></td>
                                </tr>
                            @endforeach</tbody>
                        </table>
                    </div>
                    @if ($exchanges->hasPages())<div class="profile-pagination">{{ $exchanges->links() }}</div>@endif
                @endif
            </section>
        </main>
        @include('partials.bottom-nav', ['active' => 'profile', 'variant' => 'standard'])
    </div>
@endsection

@section('scripts')
    @include('partials.profile-copy-script')
@endsection
