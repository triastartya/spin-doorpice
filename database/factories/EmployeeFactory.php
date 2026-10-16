<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        return [
            'nik' => $this->faker->unique()->numerify('EMP#####'),
            'name' => $this->faker->name(),
            'department' => $this->faker->randomElement(['IT', 'HR', 'Finance', 'Operations']),
            'email' => $this->faker->unique()->safeEmail(),
            'is_active' => true,
        ];
    }
}
