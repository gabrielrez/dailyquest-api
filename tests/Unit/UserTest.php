<?php

namespace Tests\Unit\Models;

use App\Models\User;
use App\Models\Collection;
use App\Models\Goal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function fillable_attributes()
    {
        $user = new User([
            'full_name' => 'John Doe',
            'username' => 'johndoe',
            'email' => 'john@example.com',
            'password' => 'secret',
            'profile_picture' => 'avatar.png',
        ]);

        $this->assertEquals('John Doe', $user->full_name);
        $this->assertEquals('johndoe', $user->username);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('avatar.png', $user->profile_picture);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hidden_attributes_are_not_serialized()
    {
        $user = User::factory()->create([
            'password' => 'secret',
        ]);

        $array = $user->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function casts_attributes_correctly()
    {
        $user = User::factory()->make([
            'password' => '123456',
            'email_verified_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
        $this->assertNotEquals('123456', $user->password);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function jwt_identifier_returns_primary_key()
    {
        $user = User::factory()->create();

        $this->assertEquals($user->id, $user->getJWTIdentifier());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function jwt_custom_claims_returns_empty_array()
    {
        $user = User::factory()->create();

        $this->assertEquals([], $user->getJWTCustomClaims());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function owned_collections_relationship()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $user->id]);

        $this->assertCount(1, $user->ownedCollections);
        $this->assertEquals($collection->id, $user->ownedCollections->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function collections_many_to_many_relationship()
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $user->collections()->attach($collection->id);

        $this->assertCount(1, $user->collections);
        $this->assertEquals($collection->id, $user->collections->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function goals_relationship()
    {
        $user = User::factory()->create();
        $goal = Goal::factory()->create(['owner_id' => $user->id]);

        $this->assertCount(1, $user->goals);
        $this->assertEquals($goal->id, $user->goals->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profile_picture_url_returns_path_when_exists()
    {
        $user = User::factory()->make([
            'profile_picture' => 'avatars/avatar.png',
        ]);

        $this->assertEquals(asset('storage/avatars/avatar.png'), $user->profile_picture_url);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function profile_picture_url_returns_null_when_not_exists()
    {
        $user = User::factory()->make([
            'profile_picture' => null,
        ]);

        $this->assertNull($user->profile_picture_url);
    }
}
