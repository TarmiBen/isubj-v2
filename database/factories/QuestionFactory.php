<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Question;
use App\Models\Quiz;

class QuestionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Question::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'text' => fake()->text(),
            'type' => fake()->randomElement(["multiple_choice",""]),
            'options' => '{}',
            'score_weight' => fake()->randomFloat(2, 0, 999.99),
            'meta' => '{}',
        ];
    }
}
