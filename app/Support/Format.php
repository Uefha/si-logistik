<?php

namespace App\Support;

/**
 * Format tampilan angka gaya Indonesia (titik ribuan, koma desimal).
 */
class Format
{
    /** 1234.50 -> "1.234,5"; 100.00 -> "100"; null -> "-". */
    public static function angka(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }

        $teks = number_format((float) $nilai, 2, ',', '.');

        return str_contains($teks, ',') ? rtrim(rtrim($teks, '0'), ',') : $teks;
    }

    /** 55000 -> "Rp 55.000"; null -> "-". */
    public static function rupiah(mixed $nilai): string
    {
        if ($nilai === null || $nilai === '') {
            return '-';
        }

        return 'Rp '.self::angka($nilai);
    }
}
