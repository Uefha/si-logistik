<?php

namespace App\Enums;

enum TipeTransaksi: string
{
    case Masuk = 'masuk';
    case Keluar = 'keluar';
    case Penyesuaian = 'penyesuaian';

    public function label(): string
    {
        return match ($this) {
            self::Masuk => 'Barang Masuk',
            self::Keluar => 'Barang Keluar',
            self::Penyesuaian => 'Penyesuaian Stok',
        };
    }

    /** Awalan nomor transaksi: IN-, OUT-, ADJ-. */
    public function prefix(): string
    {
        return match ($this) {
            self::Masuk => 'IN',
            self::Keluar => 'OUT',
            self::Penyesuaian => 'ADJ',
        };
    }
}
