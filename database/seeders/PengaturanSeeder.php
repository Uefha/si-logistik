<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanSeeder extends Seeder
{
    /**
     * Isi pengaturan awal hanya jika kuncinya belum ada, sehingga perubahan
     * yang dibuat admin lewat aplikasi tidak tertimpa saat seeder dijalankan ulang.
     */
    public function run(): void
    {
        $bawaan = [
            'nama_aplikasi' => config('logistik.nama_aplikasi'),
            'nama_singkat' => config('logistik.nama_singkat'),
            'nama_instansi' => config('logistik.nama_instansi'),
        ];

        $sekarang = now();

        foreach ($bawaan as $kunci => $nilai) {
            if (DB::table('pengaturan')->where('key', $kunci)->exists()) {
                continue;
            }

            DB::table('pengaturan')->insert([
                'key' => $kunci,
                'value' => $nilai,
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ]);
        }
    }
}
