<?php

namespace App\Support;

final class Money
{
    public static function normalizeUsd(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return $whole.'.'.str_pad($fraction, 2, '0');
    }

    public static function formatUsd(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '00');
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;

        return '$'.$whole.'.'.str_pad($fraction, 2, '0');
    }

    public static function formatInr(string $amount): string
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '00');
        $whole = preg_replace('/\B(?=(\d{3})+(?!\d))/', ',', $whole) ?? $whole;

        return '₹'.$whole.'.'.str_pad($fraction, 2, '0');
    }
}
