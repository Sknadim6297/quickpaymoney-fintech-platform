@extends('layouts.admin')
@section('title', 'Quick PayMoney | Exchange Details')
@section('admin-content')
    <div class="admin-page-heading">
        <div><span class="admin-eyebrow">OPERATIONS / REQUEST DETAILS</span><h1>Exchange request <span class="admin-title-id">#{{ $exchange->id }}</span></h1><p>Workflow updates are audited and notified to the user. No funds are settled.</p></div>
        <a class="admin-button admin-button-secondary" href="{{ route('admin.exchanges.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to requests</a>
    </div>
    <div class="admin-detail-grid">
        <section class="admin-panel">
            <div class="admin-panel-heading"><div><span class="admin-eyebrow">REQUEST SUMMARY</span><h2>Request details</h2></div><span class="portal-badge {{ $exchange->status }}"><span class="admin-badge-dot"></span>{{ ucfirst($exchange->status) }}</span></div>
            <dl class="admin-detail-list">
                <div><dt>User</dt><dd><a class="admin-inline-link" href="{{ route('admin.users.show', $exchange->user) }}">{{ $exchange->user->name }}</a><small>{{ $exchange->user->email }}</small></dd></div>
                <div><dt>USDT amount</dt><dd>{{ $exchange->usdt_amount }} USDT</dd></div>
                <div><dt>Reference rate</dt><dd>{{ $exchange->exchange_rate }} INR / USDT</dd></div>
                <div><dt>INR amount</dt><dd>₹{{ $exchange->inr_amount }}</dd></div>
                <div><dt>Created</dt><dd>{{ $exchange->created_at->format('M j, Y · H:i') }}</dd></div>
                <div><dt>Transaction reference</dt><dd>{{ $exchange->transaction_reference ?: 'Not supplied' }}</dd></div>
            </dl>
        </section>
        <section class="admin-panel">
            <div class="admin-panel-heading"><div><span class="admin-eyebrow">WORKFLOW</span><h2>Update request</h2><p>Every change is stored in the audit history.</p></div></div>
            @if (in_array($exchange->status, ['pending', 'processing'], true))
                <form class="admin-form" method="POST" action="{{ route('admin.exchanges.update', $exchange) }}" data-confirm="This records an administrative workflow change. It does not transfer funds." data-confirm-title="Save exchange update?" data-confirm-button="Save update">
                    @csrf @method('PUT')
                    <label for="status">Next status</label>
                    <select class="portal-input" id="status" name="status" required>
                        @if ($exchange->status === 'pending')<option value="processing">Processing</option><option value="rejected">Rejected</option>@else<option value="completed">Completed</option><option value="rejected">Rejected</option>@endif
                    </select>
                    @error('status')<span class="admin-field-error">{{ $message }}</span>@enderror
                    <label for="transaction_reference">Transaction reference <span>(required for completion)</span></label>
                    <input class="portal-input" id="transaction_reference" name="transaction_reference" value="{{ old('transaction_reference', $exchange->transaction_reference) }}" maxlength="150">
                    @error('transaction_reference')<span class="admin-field-error">{{ $message }}</span>@enderror
                    <label for="admin_notes">Admin notes</label>
                    <textarea class="portal-input" id="admin_notes" name="admin_notes" rows="4" maxlength="5000">{{ old('admin_notes', $exchange->admin_notes) }}</textarea>
                    @error('admin_notes')<span class="admin-field-error">{{ $message }}</span>@enderror
                    <button class="admin-button" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Save status</button>
                </form>
            @else
                <div class="admin-inline-notice"><i class="bi bi-lock" aria-hidden="true"></i><p>This request is terminal and cannot be changed.</p></div>
                <div class="admin-detail-list admin-notes-list"><div><dt>Admin notes</dt><dd>{{ $exchange->admin_notes ?: 'None' }}</dd></div></div>
            @endif
        </section>
    </div>
    <section class="admin-panel admin-section-gap">
        <div class="admin-panel-heading"><div><span class="admin-eyebrow">AUDIT TRAIL</span><h2>Transaction history</h2><p>Recorded events related to this request.</p></div></div>
        <div class="admin-activity-list">
            @forelse ($audit as $entry)
                <article class="admin-activity-item admin-activity-item-stacked"><span class="admin-activity-icon"><i class="bi bi-clock-history" aria-hidden="true"></i></span><div class="admin-activity-copy"><strong>{{ str_replace('_', ' ', $entry->event) }}</strong><span>{{ $entry->actor?->name ?? 'System' }} <span class="admin-activity-separator">·</span> {{ $entry->created_at->format('M j, Y · H:i') }}</span><pre class="admin-pre">{{ json_encode($entry->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></div></article>
            @empty<div class="admin-empty-state"><span><i class="bi bi-inbox" aria-hidden="true"></i></span><strong>No audit history</strong><p>Request events will appear here once recorded.</p></div>@endforelse
        </div>
    </section>
@endsection
