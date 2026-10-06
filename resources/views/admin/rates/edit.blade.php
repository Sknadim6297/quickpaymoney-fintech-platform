@extends('layouts.admin')
@section('title', 'Quick PayMoney | Exchange Rates')
@section('admin-content')
    <div class="admin-page-heading">
        <div><span class="admin-eyebrow">PLATFORM CONFIGURATION</span><h1>Exchange rates</h1><p>Manage the displayed USDT to INR reference rate. No quote is locked and no transfer is initiated.</p></div>
        <span class="admin-record-count"><i class="bi bi-currency-exchange" aria-hidden="true"></i> USDT / INR</span>
    </div>
    <div class="admin-detail-grid admin-rate-grid">
        <section class="admin-panel">
            <div class="admin-panel-heading"><div><span class="admin-eyebrow">CURRENT REFERENCE</span><h2>Configured rate</h2></div><span class="admin-rate-mark"><i class="bi bi-currency-rupee" aria-hidden="true"></i></span></div>
            @if ($exchangeRate)
                <div class="admin-rate-value"><span>1 USDT</span><i class="bi bi-arrow-right" aria-hidden="true"></i><strong>₹{{ $exchangeRate->rate }}</strong><span>INR</span></div>
                <p class="admin-rate-updated">Last updated {{ $exchangeRate->updated_at->format('M j, Y · H:i') }}@if ($exchangeRate->updatedBy) by {{ $exchangeRate->updatedBy->name }}@endif</p>
            @else
                <div class="admin-inline-notice"><i class="bi bi-info-circle" aria-hidden="true"></i><p>No rate is configured. Public pages will continue to show “Not configured”.</p></div>
            @endif
            <form class="admin-form admin-rate-form" method="POST" action="{{ route('admin.rates.update') }}" data-confirm="The new reference rate will be displayed publicly. No funds will be transferred." data-confirm-title="Update reference rate?" data-confirm-button="Update rate">
                @csrf @method('PUT')
                <label for="rate">INR per USDT</label>
                <input class="portal-input" id="rate" name="rate" inputmode="decimal" value="{{ old('rate', $exchangeRate?->rate) }}" placeholder="For example: 90.50000000" maxlength="21" required>
                @error('rate')<span class="admin-field-error">{{ $message }}</span>@enderror
                <p class="admin-form-help">Positive decimal only, up to 12 integer digits and 8 fractional digits. The exact decimal string is stored.</p>
                <button class="admin-button" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Save reference rate</button>
            </form>
        </section>
        <aside class="admin-rate-note"><span class="admin-rate-note-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span><h2>Reference rate only</h2><p>This value is informational. It does not create a transaction, reserve funds, or guarantee a settlement price.</p></aside>
    </div>
    <section class="admin-panel admin-section-gap">
        <div class="admin-panel-heading"><div><span class="admin-eyebrow">CHANGE LOG</span><h2>Recent rate changes</h2><p>Audit history of reference rate updates.</p></div></div>
        @if ($history->isEmpty())
            <div class="admin-empty-state"><span><i class="bi bi-clock-history" aria-hidden="true"></i></span><strong>No rate changes recorded</strong><p>Updates to the reference rate will appear here.</p></div>
        @else
            <div class="portal-table-wrap admin-table-wrap"><table class="portal-table admin-table"><thead><tr><th scope="col">Date</th><th scope="col">Changed by</th><th scope="col">Previous rate</th><th scope="col">New rate</th></tr></thead><tbody>
                @foreach ($history as $change)<tr><td><time datetime="{{ $change->created_at->toIso8601String() }}">{{ $change->created_at->format('M j, Y · H:i') }}</time></td><td>{{ $change->actor?->name ?? 'Admin account removed' }}</td><td>{{ $change->metadata['before'] ?? 'Not configured' }}</td><td><strong>{{ $change->metadata['after'] ?? '—' }}</strong></td></tr>@endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
