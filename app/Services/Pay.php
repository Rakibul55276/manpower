<?php
namespace App\Services;
class Pay
{
    public static function units($value)
    {
        $parts = explode('.', (string) $value);
        return ((int) $parts[0] * 100) + (int) str_pad($parts[1] ?? '', 2, '0');
    }
    public static function rounded($numerator, $denominator) { return intdiv($numerator + intdiv($denominator, 2), $denominator); }
    public static function decimal($units) { return number_format($units / 100, 2, '.', ''); }
    public static function money($cents) { return number_format($cents / 100, 2); }
}
