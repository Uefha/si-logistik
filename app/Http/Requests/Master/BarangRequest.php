<?php

namespace App\Http\Requests\Master;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi tambah dan ubah barang.
 * - Pada tambah: stok_awal wajib. Pada ubah: stok tidak dapat diubah (field stok tidak divalidasi,
 *   sehingga tidak muncul di validated()).
 * - Barcode boleh kosong (dibuat otomatis saat tambah, dipertahankan saat ubah).
 * - Aturan unique mencakup barang yang sudah di-soft-delete (sesuai unique index database).
 */
class BarangRequest extends FormRequest
{
    /** Batas ukuran foto dalam kilobyte (2 MB). */
    public const MAKS_FOTO_KB = 2048;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $barcode = trim((string) $this->input('barcode'));

        $this->merge([
            'kode_barang' => trim((string) $this->input('kode_barang')),
            'nama_barang' => trim((string) $this->input('nama_barang')),
            'barcode' => $barcode === '' ? null : $barcode,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('barang')?->getKey();
        $aktif = fn (string $tabel) => Rule::exists($tabel, 'id')->whereNull('deleted_at');

        $rules = [
            'kode_barang' => ['required', 'string', 'max:50', Rule::unique('barang', 'kode_barang')->ignore($id)],
            // Karakter dibatasi agar aman dibaca scanner dan dicetak sebagai Code 128.
            'barcode' => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9\-._]+$/', Rule::unique('barang', 'barcode')->ignore($id)],
            'nama_barang' => ['required', 'string', 'max:191'],
            'kategori_id' => ['required', 'integer', $aktif('kategori_barang')],
            'satuan_id' => ['required', 'integer', $aktif('satuan')],
            'lokasi_id' => ['required', 'integer', $aktif('lokasi')],
            'stok_minimum' => ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'],
            'harga' => ['nullable', 'numeric', 'min:0', 'max:99999999999', 'decimal:0,2'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:'.self::MAKS_FOTO_KB],
            'hapus_foto' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('POST')) {
            $rules['stok_awal'] = ['required', 'numeric', 'min:0', 'max:999999999', 'decimal:0,2'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_barang.unique' => 'Kode barang sudah digunakan (termasuk oleh barang yang sudah dihapus).',
            'barcode.unique' => 'Barcode sudah digunakan (termasuk oleh barang yang sudah dihapus).',
            'barcode.regex' => 'Barcode hanya boleh berisi huruf, angka, titik, strip, dan garis bawah.',
            'kategori_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'satuan_id.exists' => 'Satuan yang dipilih tidak tersedia.',
            'lokasi_id.exists' => 'Lokasi yang dipilih tidak tersedia.',
            'foto.image' => 'Foto harus berupa gambar.',
            'foto.mimes' => 'Foto harus berformat JPG, JPEG, PNG, atau WEBP.',
            'foto.max' => 'Ukuran foto maksimal 2 MB.',
            'stok_awal.decimal' => 'Stok awal maksimal 2 angka di belakang koma.',
            'stok_minimum.decimal' => 'Stok minimum maksimal 2 angka di belakang koma.',
            'harga.decimal' => 'Harga maksimal 2 angka di belakang koma.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'kode_barang' => 'kode barang',
            'barcode' => 'barcode',
            'nama_barang' => 'nama barang',
            'kategori_id' => 'kategori',
            'satuan_id' => 'satuan',
            'lokasi_id' => 'lokasi',
            'stok_awal' => 'stok awal',
            'stok_minimum' => 'stok minimum',
            'harga' => 'harga',
            'deskripsi' => 'deskripsi',
            'foto' => 'foto',
            'is_active' => 'status',
        ];
    }
}
