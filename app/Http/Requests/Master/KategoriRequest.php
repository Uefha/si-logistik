<?php

namespace App\Http\Requests\Master;

class KategoriRequest extends MasterNamaRequest
{
    protected function tabel(): string
    {
        return 'kategori_barang';
    }

    protected function parameterRute(): string
    {
        return 'kategori';
    }

    protected function label(): string
    {
        return 'kategori';
    }
}
