<?php

namespace App\Enums;

enum JenisMutasi: string
{
    case StokAwal = 'stok_awal';
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Penyesuaian = 'penyesuaian';

    public function label(): string
    {
        return match ($this) {
            self::StokAwal => 'Stok Awal',
            self::Masuk => 'Barang Masuk',
            self::Keluar => 'Barang Keluar',
            self::Penyesuaian => 'Penyesuaian Stok',
        };
    }
}
