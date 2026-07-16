<?php

namespace Tests\Feature;

use App\Jobs\GenerateReportJob;
use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_auto_generated_reports_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $report1 = Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'month',
        ]);

        $report2 = Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'day',
        ]);

        $this->actingAs($user)
            ->getJson(route('report.index'))
            ->assertOk()
            ->assertJsonFragment(['id' => $report1->id])
            ->assertJsonFragment(['id' => $report2->id]);
    }

    public function test_index_filters_reports_by_type(): void
    {
        $user = User::factory()->create();

        $report = Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'month',
        ]);

        Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'day',
        ]);

        $this->actingAs($user)
            ->getJson(route('report.index', ['type' => 'month']))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $report->id]);
    }

    public function test_index_does_not_return_other_users_reports(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Report::factory()->create(['user_id' => $other->id, 'type' => 'month']);

        $this->actingAs($user)
            ->getJson(route('report.index'))
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_generate_returns_report_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('report.generate'), ['type' => 'month']);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => ['month', 'collections', 'goals'],
            ]);
    }

    public function test_generate_throws_validation_error_if_type_is_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('report.generate'), [])
            ->assertStatus(422);
    }

    public function test_queue_report_dispatches_job_when_no_report_exists_for_the_period(): void
    {
        Queue::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('report.generate.queue'), ['type' => 'month'])
            ->assertOk()
            ->assertJson(['message' => 'Report Queued']);

        Queue::assertPushed(GenerateReportJob::class);
    }

    public function test_queue_report_does_not_dispatch_job_when_a_report_already_exists_for_the_period(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        Report::factory()->create([
            'user_id' => $user->id,
            'type' => 'month',
            'created_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson(route('report.generate.queue'), ['type' => 'month'])
            ->assertOk();

        Queue::assertNotPushed(GenerateReportJob::class);
    }
}
