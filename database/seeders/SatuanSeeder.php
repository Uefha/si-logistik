<?php

namespace Database\Seeders;

class SatuanSeeder extends MasterSeeder
{
    public function run(): void
    {
        $this->isiNama('satuan', [
            'Pcs',
            'Unit',
            'Buah',
            'Box',
            'Paket',
            'Rim',
            'Lusin',
            'Liter',
            'Meter',
            'Kg',
            'Botol',
            'Dus',
        ]);
    }
}
