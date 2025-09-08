<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'full_name' => 'Gabriel Rezende',
            'username'  => 'garezp',
            'email'     => 'gabrielrezcpessoa@gmail.com',
            'password'  => Hash::make('password'),
        ]);

        User::factory(4)->create();
    }
}
