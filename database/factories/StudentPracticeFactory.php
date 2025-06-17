<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Inscription;
use App\Models\Practice;
use App\Models\PracticeType;
use App\Models\Student_practice;
use App\Models\Teacher;

class StudentPracticeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = StudentPractice::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'inscription_id' => Inscription::factory(),
            'practice_id' => Practice::factory(),
            'practice_type_id' => PracticeType::factory(),
            'scenario' => fake()->text(),
            'scheduled_at' => fake()->dateTime(),
            'completed_at' => fake()->dateTime(),
            'status' => fake()->randomElement(["practice_status"]),
            'instructor_id' => Teacher::factory(),
            'result' => fake()->text(),
            'observations' => fake()->text(),
            'meta' => '{}',
        ];
    }
}
