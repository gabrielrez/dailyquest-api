<?php

namespace App\Http\Services;

use App\Http\Interfaces\ReportInterface;
use App\Http\Services\Reports\DayReport;
use App\Http\Services\Reports\MonthReport;
use App\Http\Services\Reports\OverallReport;
use App\Jobs\GenerateReportJob;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use InvalidArgumentException;

class ReportService
{
    protected array $types = [
        'overall' => OverallReport::class,
        'day' => DayReport::class,
        'month' => MonthReport::class,
    ];

    protected int $cache_timeout = 300;

    public function make(string $type): ReportInterface
    {
        if (!isset($this->types[$type])) {
            throw new InvalidArgumentException("Invalid report type: {$type}");
        }

        return new $this->types[$type];
    }

    public function generateCachedReport(User $user, string $type): array|string
    {
        $cache_key = "report:{$user->id}:{$type}";

        if ($cached = Redis::get($cache_key)) {
            return json_decode($cached, true);
        }

        $report = $this
            ->make($type)
            ->generate($user);

        Redis::setex($cache_key, $this->cache_timeout, json_encode($report));

        return $report;
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

    public function filter(Request $request)
    {
        $reports = $request->user()->reports();

        if ($request->has('type')) {
            $reports->where('type', $request->query('type'));
        }

        return $reports->get();
    }
}
