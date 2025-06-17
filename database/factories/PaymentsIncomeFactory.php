<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Payments_income;
use App\Models\Student;

class PaymentsIncomeFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PaymentsIncome::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'student_id' => Student::factory(),
            'paymentable_id' => fake()->numberBetween(-10000, 10000),
            'paymentable_type' => fake()->regexify('[A-Za-z0-9]{100}'),
            'payment' => fake()->regexify('[A-Za-z0-9]{100}'),
            'amount' => fake()->randomFloat(2, 0, 999.99),
            'discount' => fake()->randomFloat(2, 0, 999.99),
            'status' => fake()->randomElement(["paid",""]),
            'meta' => '{}',
        ];
    }
}
