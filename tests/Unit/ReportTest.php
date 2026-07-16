<?php

namespace Tests\Unit;

use App\Models\Report;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_belongs_to_a_user(): void
    {
        $user = User::factory()->create();
        $report = Report::factory()->for($user)->create();

        $this->assertInstanceOf(User::class, $report->user);
        $this->assertSame($user->id, $report->user->id);
    }

    public function test_data_is_cast_to_array(): void
    {
        $report = Report::factory()->create([
            'data' => ['goals' => ['total_completed' => 5]],
        ]);

        $this->assertIsArray($report->data);
        $this->assertSame(5, $report->data['goals']['total_completed']);
    }
}
