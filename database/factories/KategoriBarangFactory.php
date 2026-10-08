<?php

namespace Database\Factories;

use App\Models\KategoriBarang;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<KategoriBarang>
 */
class KategoriBarangFactory extends Factory
{
    protected $model = KategoriBarang::class;

    public function definition(): array
    {
        return [
            'nama' => 'Kategori '.fake()->unique()->bothify('??-###'),
        ];
    }
}
