<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Payment;
use App\Models\PaymentsIncome;
use App\Models\User;

class PaymentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'payment_id' => PaymentsIncome::factory(),
            'user_id' => User::factory(),
            'payment_reference' => fake()->regexify('[A-Za-z0-9]{200}'),
            'amount' => fake()->randomFloat(2, 0, 999.99),
            'paid_at' => fake()->dateTime(),
            'payments_income_id' => PaymentsIncome::factory(),
        ];
    }
}
