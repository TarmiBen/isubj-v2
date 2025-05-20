<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Teacher;

class TeacherFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Teacher::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'last_name1' => fake()->regexify('[A-Za-z0-9]{100}'),
            'last_name2' => fake()->word(),
            'email' => fake()->safeEmail(),
        ];
    }
}
