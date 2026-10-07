<?php

namespace Database\Factories;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Lokasi;
use App\Models\Satuan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory berjalan tanpa mass-assignment guard, sehingga `stok` dapat diisi di sini
 * (hanya untuk data uji). Barang dari factory tidak otomatis memiliki baris stok_mutasi.
 *
 * @extends Factory<Barang>
 */
class BarangFactory extends Factory
{
    protected $model = Barang::class;

    public function definition(): array
    {
        return [
            'kode_barang' => 'BRG-'.fake()->unique()->numerify('######'),
            'barcode' => '200'.fake()->unique()->numerify('#########').'0',
            'nama_barang' => ucfirst(fake()->unique()->words(3, true)),
            'kategori_id' => KategoriBarang::factory(),
            'satuan_id' => Satuan::factory(),
            'lokasi_id' => Lokasi::factory(),
            'stok' => fake()->numberBetween(20, 200),
            'stok_minimum' => 10,
            'harga' => fake()->boolean(70) ? fake()->numberBetween(1, 500) * 1000 : null,
            'deskripsi' => fake()->optional(0.5)->sentence(),
            'foto' => null,
            'is_active' => true,
        ];
    }

    /** stok = 0 */
    public function habis(): static
    {
        return $this->state(fn () => ['stok' => 0]);
    }

    /** 0 < stok <= stok_minimum */
    public function menipis(): static
    {
        return $this->state(fn () => ['stok' => 5, 'stok_minimum' => 10]);
    }

    /** stok > stok_minimum */
    public function aman(): static
    {
        return $this->state(fn () => ['stok' => 50, 'stok_minimum' => 10]);
    }

    public function nonaktif(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
