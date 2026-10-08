<?php

namespace App\Services;

use App\Enums\TipeTransaksi;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use LogicException;

class NomorTransaksiService
{
    /** Nomor dibuat hanya di dalam transaksi database pemanggil. */
    public function berikutnya(TipeTransaksi $tipe, ?CarbonImmutable $tanggal = null): string
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('Nomor transaksi harus dibuat di dalam transaksi database.');
        }

        $tanggal ??= CarbonImmutable::today(config('app.timezone'));
        $tanggalSql = $tanggal->toDateString();

        // Membuat baris awal secara aman, lalu mengunci baris tersebut untuk increment atomik.
        DB::table('nomor_urut')->insertOrIgnore([
            'tipe' => $tipe->value,
            'tanggal' => $tanggalSql,
            'terakhir' => 0,
        ]);

        $urut = DB::table('nomor_urut')
            ->where('tipe', $tipe->value)
            ->where('tanggal', $tanggalSql)
            ->lockForUpdate()
            ->first();

        $selanjutnya = (int) $urut->terakhir + 1;

        DB::table('nomor_urut')
            ->where('id', $urut->id)
            ->update(['terakhir' => $selanjutnya]);

        return sprintf('%s-%s-%04d', $tipe->prefix(), $tanggal->format('Ymd'), $selanjutnya);
    }
}
