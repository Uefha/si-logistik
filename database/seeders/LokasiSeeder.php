<?php

namespace Database\Seeders;

class LokasiSeeder extends MasterSeeder
{
    public function run(): void
    {
        $this->isiNama('lokasi', [
            'Gudang Utama',
            'Gudang ATK',
            'Gudang Peralatan',
            'Gudang Kebersihan',
            'Ruang Logistik',
            'Rak A',
            'Rak B',
            'Rak C',
            'Lemari 1',
            'Lemari 2',
        ]);
    }
}
