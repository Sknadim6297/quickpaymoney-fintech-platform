<?php

namespace App\Http\Controllers;

use App\Models\ExchangeRate;
use Illuminate\View\View;

class PublicPagesController extends Controller
{
    public function home(): View
    {
        return view('pages.index', ['exchangeRate' => $this->currentRate()]);
    }

    public function exchange(): View
    {
        return view('pages.exchange', ['exchangeRate' => $this->currentRate()]);
    }

    private function currentRate(): ?ExchangeRate
    {
        return ExchangeRate::where('pair', 'USDT_INR')->first();
    }
}
