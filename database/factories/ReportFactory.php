<?php

namespace Database\Factories;

use App\Http\Enums\CollectionStatusEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Collection>
 */
class ReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => $this->faker->randomElement(['day', 'month', 'overall']),
            'data' => json_encode(['example' => $this->faker->word]),
        ];
    }
}
