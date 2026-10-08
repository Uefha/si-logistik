<?php

namespace Database\Factories;

use App\Models\Satuan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Satuan>
 */
class SatuanFactory extends Factory
{
    protected $model = Satuan::class;

    public function definition(): array
    {
        return [
            'nama' => 'Satuan '.fake()->unique()->bothify('??-###'),
        ];
    }
}
