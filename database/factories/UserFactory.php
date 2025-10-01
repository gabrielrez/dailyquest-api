<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'full_name' => fake()->name(),
            'username'  => fake()->userName(),
            'email'     => fake()->unique()->safeEmail(),
            'password'  => Hash::make('password'),
        ];
    }
}
