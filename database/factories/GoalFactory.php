<?php

namespace Database\Factories;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Goal>
 */
class GoalFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'         => $this->faker->words(3, true),
            'description'  => $this->faker->sentence(10),
            'status'       => $this->faker->randomElement(['to_do', 'in_progress', 'done']),
            'collection_id' => Collection::factory(),
            'owner_id'     => User::factory(),
        ];
    }
}
