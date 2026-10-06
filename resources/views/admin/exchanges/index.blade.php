@extends('layouts.admin')
@section('title', 'Quick PayMoney | Exchange Requests')
@section('admin-content')
    <div class="admin-page-heading">
        <div><span class="admin-eyebrow">OPERATIONS</span><h1>Exchange requests</h1><p>Review and manage the request workflow. No funds are transferred or settled.</p></div>
        <span class="admin-record-count"><i class="bi bi-arrow-left-right" aria-hidden="true"></i> {{ number_format($exchanges->total()) }} matching requests</span>
    </div>
    <section class="admin-panel admin-directory">
        <div class="admin-panel-heading"><div><span class="admin-eyebrow">REQUEST QUEUE</span><h2>All requests</h2><p>Search by user or transaction reference and filter by status.</p></div></div>
        <form class="admin-filters" method="GET" action="{{ route('admin.exchanges.index') }}">
            <div class="admin-search-field"><label class="visually-hidden" for="search">Search user or reference</label><i class="bi bi-search" aria-hidden="true"></i><input class="portal-input" id="search" name="search" value="{{ request('search') }}" maxlength="120" placeholder="Search user or transaction reference"></div>
            <div class="admin-filter-select"><label class="visually-hidden" for="status">Exchange status</label><select class="portal-input" id="status" name="status"><option value="">All statuses</option>@foreach (\App\Models\ExchangeRequest::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select><i class="bi bi-chevron-down" aria-hidden="true"></i></div>
            <button class="admin-button" type="submit"><i class="bi bi-funnel" aria-hidden="true"></i> Apply filters</button>
            @if (request()->filled('search') || request()->filled('status'))<a class="admin-button admin-button-secondary" href="{{ route('admin.exchanges.index') }}">Clear</a>@endif
        </form>
        <div class="admin-table-meta"><span>Showing <strong>{{ $exchanges->firstItem() ?? 0 }}–{{ $exchanges->lastItem() ?? 0 }}</strong> of <strong>{{ $exchanges->total() }}</strong> requests</span></div>
        <div class="portal-table-wrap admin-table-wrap"><table class="portal-table admin-table">
            <thead><tr><th scope="col">Request</th><th scope="col">User</th><th scope="col">USDT</th><th scope="col">Rate</th><th scope="col">INR</th><th scope="col">Status</th><th scope="col">Created</th><th scope="col"><span class="visually-hidden">Actions</span></th></tr></thead><tbody>
            @forelse ($exchanges as $exchange)
                <tr><td><span class="admin-id">#{{ $exchange->id }}</span></td><td><div class="admin-table-primary">{{ $exchange->user->name }}</div><div class="admin-table-secondary">{{ $exchange->user->email }}</div></td><td>{{ $exchange->usdt_amount }}</td><td>{{ $exchange->exchange_rate }}</td><td>{{ $exchange->inr_amount }}</td><td><span class="portal-badge {{ $exchange->status }}"><span class="admin-badge-dot"></span>{{ ucfirst($exchange->status) }}</span></td><td><time datetime="{{ $exchange->created_at->toDateString() }}">{{ $exchange->created_at->format('M j, Y') }}</time></td><td class="admin-table-action"><a class="admin-icon-button" href="{{ route('admin.exchanges.show', $exchange) }}" aria-label="Review request {{ $exchange->id }}"><i class="bi bi-arrow-up-right" aria-hidden="true"></i></a></td></tr>
            @empty<tr><td colspan="8"><div class="admin-empty-state"><span><i class="bi bi-inbox" aria-hidden="true"></i></span><strong>No exchange requests found</strong><p>There are no requests matching these filters.</p></div></td></tr>@endforelse
            </tbody></table></div>
        @if ($exchanges->hasPages())<div class="admin-pagination">{{ $exchanges->links() }}</div>@endif
    </section>
@endsection
