<?php

namespace App\Http\Requests\Master;

class LokasiRequest extends MasterNamaRequest
{
    protected function tabel(): string
    {
        return 'lokasi';
    }

    protected function parameterRute(): string
    {
        return 'lokasi';
    }

    protected function label(): string
    {
        return 'lokasi';
    }

    public function rules(): array
    {
        return parent::rules() + [
            'keterangan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $keterangan = trim((string) $this->input('keterangan'));
        $this->merge(['keterangan' => $keterangan === '' ? null : $keterangan]);
    }
}
