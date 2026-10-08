<?php

namespace App\Http\Requests\Master;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dasar validasi master bernama unik (kategori, satuan, lokasi).
 * Aturan unique mencakup baris yang sudah di-soft-delete, sesuai unique index database.
 */
abstract class MasterNamaRequest extends FormRequest
{
    /** Nama tabel, mis. "kategori_barang". */
    abstract protected function tabel(): string;

    /** Nama parameter rute, mis. "kategori". */
    abstract protected function parameterRute(): string;

    /** Nama untuk pesan galat, mis. "kategori". */
    abstract protected function label(): string;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['nama' => trim((string) $this->input('nama'))]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique($this->tabel(), 'nama')->ignore($this->route($this->parameterRute())?->getKey()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama.unique' => 'Nama '.$this->label().' sudah digunakan (termasuk oleh data yang sudah dihapus).',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['nama' => 'nama '.$this->label()];
    }
}
