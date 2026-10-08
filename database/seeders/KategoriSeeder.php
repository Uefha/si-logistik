<?php

namespace Database\Seeders;

class KategoriSeeder extends MasterSeeder
{
    public function run(): void
    {
        $this->isiNama('kategori_barang', [
            'ATK',
            'Peralatan Kebersihan',
            'Peralatan Kantor',
            'Peralatan Elektronik',
            'Perlengkapan Kegiatan',
            'Perlengkapan Dapur',
            'Sparepart',
            'Bahan Habis Pakai',
            'Furniture',
            'Lainnya',
        ]);
    }
}
