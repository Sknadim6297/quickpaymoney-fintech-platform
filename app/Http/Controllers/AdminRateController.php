<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ExchangeRate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminRateController extends Controller
{
    public function show(): View
    {
        return view('admin.rates.edit', [
            'exchangeRate' => ExchangeRate::where('pair', 'USDT_INR')->first(),
            'history' => AuditLog::where('event', 'admin.exchange_rate_updated')
                ->with('actor')
                ->latest()
                ->limit(20)
                ->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'rate' => ['required', 'string', 'regex:/^(?=.*[1-9])\d{1,12}(?:\.\d{1,8})?$/'],
        ]);
        $admin = $request->user('admin');

        DB::transaction(function () use ($admin, $request, $validated): void {
            $now = now();
            $inserted = DB::table('exchange_rates')->insertOrIgnore([
                'pair' => 'USDT_INR',
                'rate' => $validated['rate'],
                'updated_by_user_id' => $admin->id,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $rate = ExchangeRate::where('pair', 'USDT_INR')->lockForUpdate()->firstOrFail();
            $before = $inserted === 1 ? null : $rate->rate;
            $rate->forceFill([
                'rate' => $validated['rate'],
                'updated_by_user_id' => $admin->id,
            ])->save();

            AuditLog::create([
                'actor_user_id' => $admin->id,
                'subject_user_id' => $admin->id,
                'event' => 'admin.exchange_rate_updated',
                'metadata' => ['pair' => 'USDT_INR', 'before' => $before, 'after' => $validated['rate']],
                'ip_address' => $request->ip(),
            ]);
        });

        return back()->with('status', 'Exchange rate updated.');
    }
}
