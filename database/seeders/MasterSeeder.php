<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Dasar untuk seeder data master bernama unik (kategori, satuan, lokasi).
 *
 * Memakai query builder (bukan Model) karena Model dibuat pada Tahap 3.
 * Baris yang sudah ada, termasuk yang di-soft-delete admin, tidak disentuh.
 */
abstract class MasterSeeder extends Seeder
{
    /**
     * @param  list<string>  $daftarNama
     */
    protected function isiNama(string $tabel, array $daftarNama): void
    {
        $sekarang = now();

        foreach ($daftarNama as $nama) {
            if (DB::table($tabel)->where('nama', $nama)->exists()) {
                continue;
            }

            DB::table($tabel)->insert([
                'nama' => $nama,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);
        }
    }
}
