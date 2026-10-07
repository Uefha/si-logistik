<?php

namespace App\Services;

use App\Models\Barang;
use RuntimeException;

class BarcodeService
{
    /**
     * Awalan 200 termasuk rentang EAN-13 yang diperuntukkan bagi penggunaan internal toko,
     * sehingga tidak bentrok dengan barcode produk pabrikan.
     */
    public const PREFIX = '200';

    private const MAKS_PERCOBAAN = 25;

    /**
     * Buat barcode EAN-13 internal yang belum dipakai (termasuk oleh barang yang sudah dihapus,
     * karena unique index database juga mencakup baris soft-delete).
     */
    public function buatUnik(): string
    {
        for ($i = 0; $i < self::MAKS_PERCOBAAN; $i++) {
            $dasar = self::PREFIX.str_pad((string) random_int(0, 999_999_999), 9, '0', STR_PAD_LEFT);
            $kode = $dasar.self::hitungCheckDigit($dasar);

            if (! Barang::withTrashed()->where('barcode', $kode)->exists()) {
                return $kode;
            }
        }

        throw new RuntimeException('Gagal membuat barcode unik. Silakan coba lagi.');
    }

    /** Check digit EAN-13 dari 12 digit pertama. */
    public static function hitungCheckDigit(string $duaBelasDigit): int
    {
        if (! preg_match('/^\d{12}$/', $duaBelasDigit)) {
            throw new \InvalidArgumentException('Check digit EAN-13 membutuhkan tepat 12 digit angka.');
        }

        $jumlah = 0;
        foreach (str_split($duaBelasDigit) as $indeks => $digit) {
            $jumlah += (int) $digit * ($indeks % 2 === 0 ? 1 : 3);
        }

        return (10 - ($jumlah % 10)) % 10;
    }

    public static function validEan13(string $kode): bool
    {
        return (bool) preg_match('/^\d{13}$/', $kode)
            && self::hitungCheckDigit(substr($kode, 0, 12)) === (int) $kode[12];
    }
}
