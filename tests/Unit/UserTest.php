<?php

namespace Tests\Unit;

use App\Models\Collection;
use App\Models\Goal;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_is_hashed(): void
    {
        $user = User::factory()->create(['password' => 'secret']);

        $this->assertNotSame('secret', $user->password);
        $this->assertTrue(password_verify('secret', $user->password));
    }

    public function test_user_can_have_owned_collections(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create(['owner_id' => $user->id]);

        $owned_collections = $user->ownedCollections;

        $this->assertCount(1, $owned_collections);
        $this->assertSame($collection->id, $owned_collections->first()->id);
        $this->assertSame($user->id, $owned_collections->first()->owner_id);
        $this->assertInstanceOf(Collection::class, $owned_collections->first());
    }

    public function test_user_can_have_collaborative_collections(): void
    {
        $user = User::factory()->create();
        $collection = Collection::factory()->create();

        $user->collections()->attach($collection->id);

        $collections = $user->collections;

        $this->assertCount(1, $collections);
        $this->assertSame($collection->id, $collections->first()->id);
        $this->assertNotSame($user->id, $collections->first()->owner_id);
        $this->assertInstanceOf(Collection::class, $collections->first());
    }

    public function test_user_can_have_owned_goals(): void
    {
        $user = User::factory()->hasGoals(3)->create();

        $goals = $user->goals;

        $this->assertCount(3, $goals);
        $this->assertSame($user->id, $goals->first()->owner_id);
        $this->assertInstanceOf(Goal::class, $goals->first());
    }

    public function test_user_can_have_reports(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->create(['user_id' => $user->id]);

        $reports = $user->reports;

        $this->assertCount(1, $reports);
        $this->assertSame($report->id, $reports->first()->id);
        $this->assertInstanceOf(Report::class, $reports->first());
    }

    public function test_jwt_identifier_returns_user_id(): void
    {
        $user = User::factory()->create();

        $this->assertSame($user->id, $user->getJWTIdentifier());
    }

    public function test_profile_picture_url_returns_null_if_no_picture(): void
    {
        $user = User::factory()->create(['profile_picture' => null]);

        $this->assertNull($user->profile_picture_url);
    }

    public function test_profile_picture_url_returns_full_path_when_picture_exists(): void
    {
        $user = User::factory()->create(['profile_picture' => 'avatars/me.png']);

        $this->assertSame(asset('storage/avatars/me.png'), $user->profile_picture_url);
    }
}
