<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UsersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_returns_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson(route('users.profile'));

        $response->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.email', $user->email);
    }

    public function test_update_profile_updates_full_name_and_username(): void
    {
        $user = User::factory()->create(['full_name' => 'Old Name', 'username' => 'oldusername']);

        $response = $this->actingAs($user)->patchJson(route('users.update'), [
            'full_name' => 'New Name',
            'username' => 'newusername',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.full_name', 'New Name')
            ->assertJsonPath('data.username', 'newusername');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'full_name' => 'New Name',
            'username' => 'newusername',
        ]);
    }

    public function test_update_profile_rejects_duplicate_username(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create(['username' => 'mine']);

        $response = $this->actingAs($user)->patchJson(route('users.update'), [
            'username' => 'taken',
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_profile_picture_stores_file_and_updates_user(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this->actingAs($user)->postJson(route('users.profile-picture'), [
            'profile_picture' => $file,
        ]);

        $response->assertOk()
            ->assertJsonStructure(['data' => ['profile_picture']]);

        $user->refresh();
        $this->assertNotNull($user->profile_picture);
        Storage::disk('public')->assertExists($user->profile_picture);
    }

    public function test_upload_profile_picture_requires_a_file(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('users.profile-picture'), []);

        $response->assertStatus(422);
    }
}
