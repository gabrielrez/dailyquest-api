<?php

namespace Database\Factories;

use App\Models\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'collection_id' => Collection::factory(),
            'email' => $this->faker->safeEmail(),
            'token' => Str::uuid()->toString(),
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn() => [
            'expires_at' => now()->subDay(),
        ]);
    }
}
