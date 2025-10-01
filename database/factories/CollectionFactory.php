<?php

namespace Database\Factories;

use App\Http\Enums\CollectionStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class CollectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name'             => fake()->name(),
            'description'      => fake()->sentence(5),
            'cyclic'           => false,
            'deadline'         => null,
            'is_collaborative' => true,
            'status'           => CollectionStatusEnum::IN_PROGRESS->value,
            'owner_id'         => User::factory(),
        ];
    }
}
