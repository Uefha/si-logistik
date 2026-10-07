<?php

namespace Tests\Unit;

use App\Enums\StatusStok;
use App\Services\BarcodeService;
use App\Support\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class StatusStokTest extends TestCase
{
    /**
     * @return array<string, array{0: float|int|string, 1: float|int|string, 2: StatusStok}>
     */
    public static function kasus(): array
    {
        return [
            'stok nol' => [0, 10, StatusStok::Habis],
            'stok nol dan minimum nol' => [0, 0, StatusStok::Habis],
            'di bawah minimum' => [1, 10, StatusStok::Menipis],
            'tepat minimum' => [10, 10, StatusStok::Menipis],
            'di atas minimum' => [10.01, 10, StatusStok::Aman],
            'minimum nol, stok ada' => [5, 0, StatusStok::Aman],
            'string desimal dari database' => ['0.00', '2.00', StatusStok::Habis],
        ];
    }

    #[DataProvider('kasus')]
    public function test_status_dihitung_sesuai_aturan(float|int|string $stok, float|int|string $minimum, StatusStok $harapan): void
    {
        $this->assertSame($harapan, StatusStok::dari($stok, $minimum));
    }

    public function test_warna_badge(): void
    {
        $this->assertSame('success', StatusStok::Aman->warna());
        $this->assertSame('warning', StatusStok::Menipis->warna());
        $this->assertSame('danger', StatusStok::Habis->warna());
    }

    public function test_check_digit_ean13(): void
    {
        $this->assertSame(1, BarcodeService::hitungCheckDigit('400638133393'));
        $this->assertSame(7, BarcodeService::hitungCheckDigit('590123412345'));
        $this->assertTrue(BarcodeService::validEan13('4006381333931'));
        $this->assertFalse(BarcodeService::validEan13('4006381333932'));
        $this->assertFalse(BarcodeService::validEan13('12345'));
    }

    public function test_format_angka_dan_rupiah(): void
    {
        $this->assertSame('100', Format::angka('100.00'));
        $this->assertSame('12,5', Format::angka('12.50'));
        $this->assertSame('1.234,56', Format::angka(1234.56));
        $this->assertSame('-', Format::angka(null));
        $this->assertSame('Rp 55.000', Format::rupiah('55000.00'));
        $this->assertSame('-', Format::rupiah(null));
    }
}
