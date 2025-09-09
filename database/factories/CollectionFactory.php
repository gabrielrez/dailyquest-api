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
            'name'             => $this->faker->words(2, true),
            'description'      => $this->faker->sentence(5),
            'cyclic'           => false,
            'deadline'         => null,
            'is_collaborative' => true,
            'status'           => $this->faker->randomElement(CollectionStatusEnum::notCompleted())->value,
            'owner_id'         => User::factory(),
        ];
    }
}
