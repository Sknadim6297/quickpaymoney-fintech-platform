@php
    $notice = collect(['success', 'error', 'warning', 'info'])
        ->first(fn (string $type) => session()->has($type));

    $payload = [
        'notice' => $notice ? ['type' => $notice, 'message' => (string) session($notice)] : (
            session()->has('status') ? ['type' => 'success', 'message' => (string) session('status')] : null
        ),
        'errors' => $errors->all(),
    ];
@endphp
<script id="quickpay-notifications" type="application/json">{!! json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}</script>
