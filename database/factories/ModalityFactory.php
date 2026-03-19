<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Modality;

class ModalityFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Modality::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'code' => fake()->regexify('[A-Za-z0-9]{20}'),
            'name' => fake()->name(),
            'description' => fake()->text(),
            'status' => fake()->randomElement(["active",""]),
        ];
    }
}
