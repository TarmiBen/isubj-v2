<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Inscription;
use App\Models\Period;
use App\Models\Student;

class InscriptionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Inscription::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'period_id' => Period::factory(),
            'status' => fake()->randomElement(["active",""]),
        ];
    }
}
