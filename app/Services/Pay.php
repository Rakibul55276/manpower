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
    public static function moneyWords($cents)
    {
        $cents=(int)$cents;
        $riyals=intdiv($cents,100);
        $halalas=$cents%100;
        $words=static::integerWords($riyals).' '.($riyals===1?'riyal':'riyals');
        if($halalas)$words.=' and '.static::integerWords($halalas).' '.($halalas===1?'halala':'halalas');
        return $words.' only';
    }
    private static function integerWords($number)
    {
        $number=(int)$number;
        if($number===0)return 'zero';
        $ones=['','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'];
        $tens=['','','twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
        if($number<20)return $ones[$number];
        if($number<100)return $tens[intdiv($number,10)].($number%10?'-'.$ones[$number%10]:'');
        if($number<1000)return $ones[intdiv($number,100)].' hundred'.($number%100?' '.static::integerWords($number%100):'');
        foreach([[1000000000,'billion'],[1000000,'million'],[1000,'thousand']] as [$value,$label]){
            if($number>=$value)return static::integerWords(intdiv($number,$value)).' '.$label.($number%$value?' '.static::integerWords($number%$value):'');
        }
        return '';
    }
}
