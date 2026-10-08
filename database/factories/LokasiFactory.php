<?php

namespace Database\Factories;

use App\Models\Lokasi;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lokasi>
 */
class LokasiFactory extends Factory
{
    protected $model = Lokasi::class;

    public function definition(): array
    {
        return [
            'nama' => 'Lokasi '.fake()->unique()->bothify('??-###'),
            'keterangan' => fake()->optional(0.5)->sentence(),
        ];
    }
}
