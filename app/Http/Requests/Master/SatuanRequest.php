<?php

namespace App\Http\Requests\Master;

class SatuanRequest extends MasterNamaRequest
{
    protected function tabel(): string
    {
        return 'satuan';
    }

    protected function parameterRute(): string
    {
        return 'satuan';
    }

    protected function label(): string
    {
        return 'satuan';
    }
}
