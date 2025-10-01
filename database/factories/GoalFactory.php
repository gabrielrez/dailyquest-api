<?php

namespace Database\Factories;

use App\Http\Enums\GoalStatusEnum;
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
            'name'         => fake()->name(),
            'description'  => fake()->sentence(10),
            'status'       => collect(GoalStatusEnum::notCompleted())->random()->value,
            'collection_id' => Collection::factory(),
            'owner_id'     => User::factory(),
        ];
    }
}
