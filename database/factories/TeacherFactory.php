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
            'employee_number' => fake()->regexify('[A-Za-z0-9]{20}'),
            'first_name' => fake()->firstName(),
            'last_name1' => fake()->regexify('[A-Za-z0-9]{100}'),
            'last_name2' => fake()->regexify('[A-Za-z0-9]{100}'),
            'gender' => fake()->randomElement(["M","F","O"]),
            'date_of_birth' => fake()->date(),
            'curp' => fake()->regexify('[A-Za-z0-9]{18}'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->regexify('[A-Za-z0-9]{20}'),
            'hire_date' => fake()->date(),
            'status' => fake()->randomElement(["active","inactive","on_leave","retired"]),
            'street' => fake()->streetName(),
            'city' => fake()->city(),
            'state' => fake()->regexify('[A-Za-z0-9]{100}'),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'title' => fake()->sentence(4),
            'specialization' => fake()->regexify('[A-Za-z0-9]{150}'),
            'photo' => fake()->regexify('[A-Za-z0-9]{255}'),
            'emergency_contact_name' => fake()->regexify('[A-Za-z0-9]{150}'),
            'emergency_contact_phone' => fake()->regexify('[A-Za-z0-9]{20}'),
            'meta' => '{}',
        ];
    }
}
