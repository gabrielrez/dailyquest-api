<?php

namespace App\Jobs;

use App\Http\Services\ReportService;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateReportJob implements ShouldQueue
{
    use Queueable;

    protected User $user;
    protected string $type;

    public function __construct(User $user, string $type)
    {
        $this->user = $user;
        $this->type = $type;
    }

    public function handle(): void
    {
        $report_data = app(ReportService::class)
            ->make($this->type)
            ->generate($this->user);

        $this->user->reports()->create([
            'type' => $this->type,
            'data' => $report_data,
        ]);
    }
}
