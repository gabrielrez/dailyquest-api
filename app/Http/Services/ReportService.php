<?php

namespace App\Http\Services;

use App\Http\Interfaces\ReportInterface;
use App\Http\Services\Reports\DayReport;
use App\Http\Services\Reports\MonthReport;
use App\Http\Services\Reports\OverallReport;
use App\Jobs\GenerateReportJob;
use App\Models\User;
use Carbon\Carbon;
use InvalidArgumentException;

class ReportService
{
    protected array $types = [
        'overall' => OverallReport::class,
        'day' => DayReport::class,
        'month' => MonthReport::class,
    ];

    public function make(string $type): ReportInterface
    {
        if (!isset($this->types[$type])) {
            throw new InvalidArgumentException("Invalid report type: {$type}");
        }

        return new $this->types[$type];
    }

    public function resolveGenerateReport(User $user, ?string $type = 'month'): void
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $existing_report = $user->reports()
            ->where('type', $type)
            ->whereBetween('created_at', [$start, $end])
            ->first();

        if (!$existing_report) {
            GenerateReportJob::dispatch($user, $type);
        }
    }
}
