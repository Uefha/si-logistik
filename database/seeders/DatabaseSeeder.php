<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seluruh seeder bersifat idempotent: aman dijalankan berulang kali tanpa
     * menggandakan data atau menimpa perubahan yang sudah dibuat admin.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            KategoriSeeder::class,
            SatuanSeeder::class,
            LokasiSeeder::class,
            PengaturanSeeder::class,
        ]);
    }
}
