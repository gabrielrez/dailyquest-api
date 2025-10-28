<?php

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

describe('register', function () {
    test('user can register without invitation', function () {
        $response = $this->postJson(route('auth.register'), [
            'full_name' => 'Teste',
            'username' => 'teste',
            'email' => 'teste@teste.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'full_name', 'username', 'email'],
                    'token',
                ]
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'teste@teste.com',
        ]);
    });

    // test('user can register with a valid invitation', function () {
    //     $invitation = Invitation::factory()->create(['status' => 'pending']);

    //     $response = $this->postJson(route('auth.register'), [
    //         'full_name' => 'Teste 2',
    //         'username' => 'teste2',
    //         'email' => 'teste2@example.com',
    //         'password' => '123456',
    //         'password_confirmation' => '123456',
    //         'token' => $invitation->token,
    //     ]);

    //     $response->assertCreated()
    //         ->assertJsonStructure([
    //             'data' => [
    //                 'user' => ['id', 'full_name', 'username', 'email'],
    //                 'token',
    //             ]
    //         ]);

    //     $this->assertDatabaseHas('invitations', [
    //         'id' => $invitation->id,
    //         'status' => 'accepted',
    //     ]);

    //     $userId = $response->json('user.id');
    //     $user = User::find($userId);

    //     expect($user->collections()->pluck('id'))->toContain($invitation->collection_id);
    // });
});

describe('login', function () {
    test('user can login with valid credentials', function () {
        $password = 'password';
        $user = User::factory()->create([
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson(route('auth.login'), [
            'email' => $user->email,
            'password' => $password,
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'token_type',
                ]
            ]);
    });

    test('user cannot login with invalid credentials', function () {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson(route('auth.login'), [
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Invalid credentials',
            ]);
    });
});

describe('logout', function () {
    test('user can logout successfully', function () {
        $user = User::factory()->create();

        Auth::login($user);

        $response = $this->postJson(route('auth.logout'));

        $response->assertOk()
            ->assertJson([
                'message' => 'Logout successful',
            ]);
    });
});
