<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PenyesuaianStokRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'barang_id' => ['required', 'integer', 'exists:barang,id'],
            'stok_sistem' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'stok_fisik' => ['required', 'numeric', 'min:0', 'decimal:0,2', 'max:9999999999999.99'],
            'alasan' => ['required', 'string', 'max:5000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'barang_id.required' => 'Pilih barang yang akan disesuaikan.',
            'barang_id.exists' => 'Barang tidak ditemukan. Pilih barang yang masih tersedia.',
            'stok_sistem.required' => 'Pilih kembali barang agar stok sistem terbaru dapat diperiksa.',
            'stok_fisik.required' => 'Stok fisik wajib diisi.',
            'stok_fisik.numeric' => 'Stok fisik harus berupa angka.',
            'stok_fisik.min' => 'Stok fisik tidak boleh negatif.',
            'stok_fisik.decimal' => 'Stok fisik maksimal boleh memiliki dua angka desimal.',
            'alasan.required' => 'Alasan penyesuaian wajib diisi.',
        ];
    }
}
