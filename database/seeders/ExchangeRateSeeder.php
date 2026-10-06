<?php

namespace Database\Seeders;

use App\Models\ExchangeRate;
use Illuminate\Database\Seeder;

class ExchangeRateSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            [
                'plan_key' => 'base',
                'name' => 'Base Rate',
                'rate' => '100.00000000',
                'minimum_amount' => '0.00',
                'maximum_amount' => '9999.99',
                'label' => 'LIVE RATE',
                'description' => 'Standard USDT to INR reference rate.',
                'icon' => 'bi-currency-exchange',
            ],
            [
                'plan_key' => 'prime',
                'name' => 'Prime Rate',
                'rate' => '115.00000000',
                'minimum_amount' => '10000.00',
                'maximum_amount' => '19999.99',
                'label' => null,
                'description' => 'Preferred reference rate for qualifying deposits.',
                'icon' => 'bi-diamond-fill',
            ],
            [
                'plan_key' => 'vip',
                'name' => 'VIP Rate',
                'rate' => '120.00000000',
                'minimum_amount' => '20000.00',
                'maximum_amount' => null,
                'label' => null,
                'description' => 'VIP reference rate for qualifying deposits.',
                'icon' => 'bi-gem',
            ],
        ] as $plan) {
            ExchangeRate::firstOrCreate(
                ['plan_key' => $plan['plan_key']],
                [
                    ...$plan,
                    'pair' => 'USDT_INR',
                    'is_active' => true,
                    'is_default' => true,
                ],
            );
        }
    }
}
