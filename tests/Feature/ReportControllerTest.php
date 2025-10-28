<?php

use App\Http\Services\ReportService;
use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->reportService = app()->make(ReportService::class);
});

describe('index', function () {
    test('returns all auto-generated reports for authenticated user', function () {
        $user = User::factory()->create();

        $report1 = Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'month'
        ]);

        $report2 = Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'day'
        ]);

        $this->actingAs($user)
            ->getJson(route('report.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $report1->id])
            ->assertJsonFragment(['id' => $report2->id]);
    });

    test('filters reports by type', function () {
        $user = User::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'month'
        ]);

        Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'day'
        ]);

        $this->actingAs($user)
            ->getJson(route('report.index', ['type' => 'month']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['id' => $report->id]);
    });
});

describe('generate', function () {
    test('generates a report and caches it', function () {
        $user = User::factory()->create();
        $type = 'month';

        Redis::flushall();

        $this->actingAs($user)
            ->postJson(route('report.generate'), ['type' => $type])
            ->assertOk();

        $cache_key = "report:{$user->id}:{$type}";

        expect(Redis::exists($cache_key))->toBe(1);

        $data = json_decode(Redis::get($cache_key), true);
        expect($data)->toBeArray();
    });

    test('throws validation error if type is missing', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('report.generate'), [])
            ->assertStatus(422);
    });
});