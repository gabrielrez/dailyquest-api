<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_without_invitation(): void
    {
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
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'teste@teste.com',
        ]);
    }

    public function test_user_can_register_with_a_valid_invitation(): void
    {
        $collection = \App\Models\Collection::factory()->create();
        $invitation = Invitation::factory()->create([
            'collection_id' => $collection->id,
            'email' => 'teste2@example.com',
            'status' => 'pending',
        ]);

        $response = $this->postJson(route('auth.register'), [
            'full_name' => 'Teste 2',
            'username' => 'teste2',
            'email' => 'teste2@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
            'token' => $invitation->token,
        ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'user' => ['id', 'full_name', 'username', 'email'],
                    'token',
                ],
            ]);

        $this->assertDatabaseHas('invitations', [
            'id' => $invitation->id,
            'status' => 'accepted',
        ]);

        $user = User::where('email', 'teste2@example.com')->firstOrFail();

        $this->assertTrue($user->collections()->where('collections.id', $collection->id)->exists());
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
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
                ],
            ]);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
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
    }

    public function test_user_can_logout_successfully(): void
    {
        $user = User::factory()->create();

        Auth::login($user);

        $response = $this->postJson(route('auth.logout'));

        $response->assertOk()
            ->assertJson([
                'message' => 'Logout successful',
            ]);
    }
}
