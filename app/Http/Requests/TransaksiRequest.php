<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransaksiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $aturan = [
            'items' => ['required', 'array', 'min:1', 'max:100'],
            'items.*.barang_id' => ['required', 'integer', 'min:1', 'exists:barang,id'],
            'items.*.qty' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:9999999999999.99'],
            'keterangan' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->routeIs('transaksi.keluar.simpan')) {
            $aturan['tujuan'] = ['required', 'string', 'max:191'];
        } else {
            $aturan['tujuan'] = ['nullable', 'string', 'max:191'];
        }

        return $aturan;
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'items.required' => 'Keranjang transaksi belum berisi barang.',
            'items.min' => 'Tambahkan minimal satu barang ke keranjang.',
            'items.*.barang_id.exists' => 'Barang tidak ditemukan. Muat ulang halaman lalu coba lagi.',
            'items.*.qty.required' => 'Jumlah wajib diisi.',
            'items.*.qty.numeric' => 'Jumlah harus berupa angka.',
            'items.*.qty.gt' => 'Jumlah harus lebih dari 0.',
            'items.*.qty.decimal' => 'Jumlah maksimal boleh memiliki dua angka desimal.',
            'tujuan.required' => 'Tujuan atau penerima wajib diisi untuk barang keluar.',
        ];
    }
}
