<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\Assignment;
use App\Models\Group;
use App\Models\Subject;
use App\Models\Teacher;

class AssignmentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Assignment::class;

    /**
     * Define the model's default state.
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'teacher_id' => Teacher::factory(),
            'subject_id' => Subject::factory(),
        ];
    }
}
