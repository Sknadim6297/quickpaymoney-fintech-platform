<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExchangeRequest;
use App\Notifications\ExchangeStatusUpdated;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminExchangeController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'status' => ['nullable', Rule::in(ExchangeRequest::STATUSES)],
        ]);

        $exchanges = ExchangeRequest::with('user:id,name,email')
            ->when($validated['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('transaction_reference', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($users) => $users->where('email', 'like', '%'.$search.'%')->orWhere('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($validated['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.exchanges.index', ['exchanges' => $exchanges]);
    }

    public function show(ExchangeRequest $exchangeRequest): View
    {
        return view('admin.exchanges.show', [
            'exchange' => $exchangeRequest->load('user'),
            'audit' => AuditLog::where('auditable_type', ExchangeRequest::class)
                ->where('auditable_id', $exchangeRequest->id)->with('actor')->latest()->get(),
        ]);
    }

    public function update(Request $request, ExchangeRequest $exchangeRequest): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ExchangeRequest::STATUSES)],
            'transaction_reference' => ['nullable', 'string', 'max:150', Rule::unique('exchange_requests', 'transaction_reference')->ignore($exchangeRequest->id)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($request, $exchangeRequest, $validated): void {
            $exchange = ExchangeRequest::whereKey($exchangeRequest->id)->lockForUpdate()->firstOrFail();
            $allowed = match ($exchange->status) {
                'pending' => ['processing', 'rejected'],
                'processing' => ['completed', 'rejected'],
                default => [],
            };

            abort_unless(in_array($validated['status'], $allowed, true), 422, 'This exchange status transition is not allowed.');
            abort_if($validated['status'] === 'completed' && empty($validated['transaction_reference']), 422, 'A transaction reference is required before completion.');

            $before = ['status' => $exchange->status, 'transaction_reference' => $exchange->transaction_reference];
            $exchange->fill($validated)->save();
            AuditLog::create([
                'actor_user_id' => $request->user('admin')->id,
                'subject_user_id' => $exchange->user_id,
                'event' => 'admin.exchange_status_updated',
                'auditable_type' => ExchangeRequest::class,
                'auditable_id' => $exchange->id,
                'metadata' => ['before' => $before, 'after' => $validated],
                'ip_address' => $request->ip(),
            ]);

            $exchange->user->notify(new ExchangeStatusUpdated($exchange));
        });

        return back()->with('status', 'Exchange request updated.');
    }
}
