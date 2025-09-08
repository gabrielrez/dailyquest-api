<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Database\Seeder;

class GoalSeeder extends Seeder
{
    public function run(): void
    {
        $main_collection = Collection::where('name', 'Dailyquest API')->first();

        Goal::factory()->create([
            'name' => 'Criar API',
            'description' => 'Criar uma API para o Dailyquest',
            'status' => 'doing',
            'collection_id' => $main_collection->id,
            'owner_id' => $main_collection->owner_id,
        ]);

        $collections = Collection::all();

        foreach ($collections as $collection) {
            if ($collection->id === $main_collection->id) {
                continue;
            }

            Goal::factory()->count(3)->create([
                'collection_id' => $collection->id,
                'owner_id'      => $collection->owner_id,
            ]);
        }
    }
}
