<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Student;

class StudentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Student::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'student_number' => fake()->regexify('[A-Za-z0-9]{20}'),
            'name' => fake()->name(),
            'last_name1' => fake()->regexify('[A-Za-z0-9]{100}'),
            'last_name2' => fake()->word(),
            'gender' => fake()->randomElement(["M","F","O"]),
            'date_of_birth' => fake()->date(),
            'curp' => fake()->regexify('[A-Za-z0-9]{18}'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'street' => fake()->streetName(),
            'city' => fake()->city(),
            'state' => fake()->regexify('[A-Za-z0-9]{100}'),
            'postal_code' => fake()->postcode(),
            'country' => fake()->country(),
            'enrollment_date' => fake()->date(),
            'status' => fake()->randomElement(["active","inactive","graduated","suspended"]),
            'guardian_name' => fake()->regexify('[A-Za-z0-9]{150}'),
            'guardian_phone' => fake()->regexify('[A-Za-z0-9]{15}'),
            'emergency_contact_name' => fake()->regexify('[A-Za-z0-9]{150}'),
            'emergency_contact_phone' => fake()->regexify('[A-Za-z0-9]{15}'),
            'photo' => fake()->regexify('[A-Za-z0-9]{255}'),
        ];
    }
}
