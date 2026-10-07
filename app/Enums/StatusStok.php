<?php

namespace App\Enums;

/**
 * Status stok dihitung dinamis dari stok dan stok minimum, tidak disimpan di database.
 *
 *  stok = 0                  -> HABIS
 *  0 < stok <= stok_minimum  -> MENIPIS
 *  stok > stok_minimum       -> AMAN
 */
enum StatusStok: string
{
    case Aman = 'aman';
    case Menipis = 'menipis';
    case Habis = 'habis';

    public static function dari(float|int|string $stok, float|int|string $stokMinimum): self
    {
        $stok = (float) $stok;
        $stokMinimum = (float) $stokMinimum;

        if ($stok <= 0) {
            return self::Habis;
        }

        return $stok <= $stokMinimum ? self::Menipis : self::Aman;
    }

    public function label(): string
    {
        return match ($this) {
            self::Aman => 'AMAN',
            self::Menipis => 'MENIPIS',
            self::Habis => 'HABIS',
        };
    }

    /** Warna badge Bootstrap: hijau, kuning, merah. */
    public function warna(): string
    {
        return match ($this) {
            self::Aman => 'success',
            self::Menipis => 'warning',
            self::Habis => 'danger',
        };
    }
}
