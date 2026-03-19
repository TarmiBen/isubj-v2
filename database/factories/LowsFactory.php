<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Incripion;
use App\Models\Inscription;
use App\Models\Lows;

class LowsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Lows::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'comment' => fake()->text(),
            'inscription_id' => Inscription::factory(),
            'date' => fake()->date(),
            'type' => fake()->regexify('[A-Za-z0-9]{200}'),
            'incripion_id' => Incripion::factory(),
        ];
    }
}
