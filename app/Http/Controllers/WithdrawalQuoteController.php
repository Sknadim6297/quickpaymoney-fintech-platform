<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use App\Support\Decimal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalQuoteController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'string', 'regex:/^(?:0|[1-9]\d{0,11})(?:\.\d{1,8})?$/'],
        ]);

        $amount = $validated['amount'];
        if (Decimal::compare($amount, '0', 8) <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Enter a USDT amount greater than zero.',
            ]);
        }

        $plan = ExchangeRate::query()
            ->where('is_active', true)
            ->where('minimum_amount', '<=', $amount)
            ->where(function ($query) use ($amount): void {
                $query->whereNull('maximum_amount')
                    ->orWhere('maximum_amount', '>=', $amount);
            })
            ->orderByDesc('minimum_amount')
            ->first();

        if (! $plan) {
            throw ValidationException::withMessages([
                'amount' => 'No active rate slab matches this amount. Please contact support.',
            ]);
        }

        return response()->json([
            'plan' => $plan->name,
            'label' => $plan->label,
            'rate' => $plan->formattedRate(),
            'amount' => $amount,
            'estimated_inr' => Decimal::multiplyToCents($amount, (string) $plan->rate),
        ]);
    }
}
