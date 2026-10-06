@extends('layouts.admin')
@section('title', 'Quick PayMoney | User Details')
@section('admin-content')
    <div class="admin-page-heading">
        <div><span class="admin-eyebrow">ACCOUNT DIRECTORY / USER DETAILS</span><h1>{{ $user->name }}</h1><p>Account profile, access settings, and recorded activity.</p></div>
        <a class="admin-button admin-button-secondary" href="{{ route('admin.users.index') }}"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to users</a>
    </div>
    <div class="admin-detail-grid">
        <section class="admin-panel">
            <div class="admin-panel-heading"><div><span class="admin-eyebrow">PROFILE</span><h2>Account information</h2></div></div>
            <div class="admin-profile-identity"><span class="admin-avatar admin-avatar-large" aria-hidden="true">{{ mb_substr($user->name, 0, 1) }}</span><span><strong>{{ $user->name }}</strong><small>{{ $user->email }}</small></span></div>
            <dl class="admin-detail-list">
                <div><dt>Mobile</dt><dd>{{ $user->mobile ?: 'Not provided' }}</dd></div>
                <div><dt>Role</dt><dd>{{ ucfirst($user->role) }}</dd></div>
                <div><dt>Registered</dt><dd>{{ $user->created_at->format('M j, Y · H:i') }}</dd></div>
                <div><dt>Email verified</dt><dd><span class="portal-badge {{ $user->hasVerifiedEmail() ? 'verified' : 'pending' }}"><span class="admin-badge-dot"></span>{{ $user->hasVerifiedEmail() ? 'Verified' : 'Not verified' }}</span></dd></div>
                <div><dt>Verification status</dt><dd><span class="portal-badge {{ $user->verification_status }}"><span class="admin-badge-dot"></span>{{ ucfirst($user->verification_status) }}</span></dd></div>
            </dl>
        </section>
        <section class="admin-panel">
            <div class="admin-panel-heading"><div><span class="admin-eyebrow">ACCESS CONTROL</span><h2>Manage account access</h2><p>Changes take effect immediately and are recorded.</p></div></div>
            @if ($user->id === auth('admin')->id())
                <div class="admin-inline-notice"><i class="bi bi-info-circle" aria-hidden="true"></i><p>Your own admin role and account status cannot be changed from this session.</p></div>
            @else
                <form class="admin-form" method="POST" action="{{ route('admin.users.update', $user) }}" data-confirm="The selected role and account status will take effect immediately." data-confirm-title="Update account access?" data-confirm-button="Save changes">
                    @csrf @method('PUT')
                    <label for="account_status">Account status</label><select class="portal-input" id="account_status" name="account_status" required>@foreach (['active', 'suspended', 'blocked'] as $status)<option value="{{ $status }}" @selected($user->account_status === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                    <label for="role">Role</label><select class="portal-input" id="role" name="role" required>@foreach (['user', 'admin'] as $role)<option value="{{ $role }}" @selected($user->role === $role)>{{ ucfirst($role) }}</option>@endforeach</select>
                    <button class="admin-button" type="submit"><i class="bi bi-check2" aria-hidden="true"></i> Save access settings</button>
                </form>
            @endif
        </section>
    </div>
    <section class="admin-panel admin-section-gap">
        <div class="admin-panel-heading"><div><span class="admin-eyebrow">ACCOUNT ACTIVITY</span><h2>Exchange and transaction history</h2></div></div>
        @if ($exchanges->isEmpty())<div class="admin-empty-state"><span><i class="bi bi-arrow-left-right" aria-hidden="true"></i></span><strong>No exchange records</strong><p>Exchange requests for this account will appear here.</p></div>@else
            <div class="portal-table-wrap admin-table-wrap"><table class="portal-table admin-table"><thead><tr><th scope="col">Request</th><th scope="col">USDT</th><th scope="col">Rate</th><th scope="col">INR</th><th scope="col">Status</th><th scope="col">Reference</th></tr></thead><tbody>
                @foreach ($exchanges as $exchange)<tr><td><span class="admin-id">#{{ $exchange->id }}</span></td><td>{{ $exchange->usdt_amount }}</td><td>{{ \App\Models\ExchangeRate::formatDecimal($exchange->exchange_rate) }}</td><td>{{ $exchange->inr_amount }}</td><td><span class="portal-badge {{ $exchange->status }}"><span class="admin-badge-dot"></span>{{ ucfirst($exchange->status) }}</span></td><td>{{ $exchange->transaction_reference ?: '—' }}</td></tr>@endforeach
            </tbody></table></div><div class="admin-pagination">{{ $exchanges->links() }}</div>
        @endif
    </section>
    <section class="admin-panel admin-section-gap">
        <div class="admin-panel-heading"><div><span class="admin-eyebrow">SECURITY TRAIL</span><h2>Recent activity log</h2></div></div>
        <div class="admin-activity-list">
            @forelse ($activity as $entry)<article class="admin-activity-item"><span class="admin-activity-icon"><i class="bi bi-shield-check" aria-hidden="true"></i></span><div class="admin-activity-copy"><strong>{{ str_replace('_', ' ', $entry->event) }}</strong><span>{{ $entry->actor?->name ?? 'System' }}</span></div><time datetime="{{ $entry->created_at->toIso8601String() }}">{{ $entry->created_at->format('M j, Y · H:i') }}</time></article>
            @empty<div class="admin-empty-state"><span><i class="bi bi-inbox" aria-hidden="true"></i></span><strong>No activity recorded</strong><p>Account events will be listed here.</p></div>@endforelse
        </div>
    </section>
@endsection
