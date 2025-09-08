<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollectionSeeder extends Seeder
{
    public function run(): void
    {
        $main_user = User::where('username', 'garezp')->first();

        Collection::factory()->create([
            'name' => 'Dailyquest API',
            'description' => 'Desenvolvimento da API do Dailyquest',
            'status' => 'in_progress',
            'cyclic' => false,
            'deadline' => null,
            'is_collaborative' => true,
            'owner_id' => $main_user->id,
            'expired_at' => null,
        ]);

        foreach (User::all() as $user) {
            if ($user->id === $main_user->id) {
                continue;
            }

            Collection::factory()->count(2)->create([
                'owner_id' => $user->id,
            ]);
        }
    }
}
