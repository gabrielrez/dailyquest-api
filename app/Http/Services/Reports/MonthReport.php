<?php

namespace App\Http\Services\Reports;

use App\Http\Interfaces\ReportInterface;
use App\Models\User;
use Carbon\Carbon;

class MonthReport implements ReportInterface
{
    public function generate(User $user): array
    {
        $now = Carbon::now();
        $days_until_now = $now->day;
        $start = $now->copy()->startOfMonth();
        $end = $now->copy()->endOfMonth();

        $collections = $user->ownedCollections()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->get(['id', 'completed_at']);

        $collections_per_day = $collections
            ->groupBy(fn($collection) => $collection->completed_at->format('Y-m-d'))
            ->map->count();

        $collections_average = $days_until_now > 0
            ? $collections->count() / $days_until_now
            : 0;

        $goals = $user->goals()
            ->where('status', 'done')
            ->whereBetween('done_at', [$start, $end])
            ->get(['id', 'done_at']);

        $goals_per_day = $goals
            ->groupBy(fn($goal) => $goal->done_at->format('Y-m-d'))
            ->map->count();

        $goals_average = $days_until_now > 0
            ? $goals->count() / $days_until_now
            : 0;

        return [
            'month' => $now->format('F Y'),
            'collections' => [
                'total_completed' => $collections->count(),
                'average_completed' => round($collections_average, 2),
                'completed_per_day' => $collections_per_day,
            ],
            'goals' => [
                'total_completed' => $goals->count(),
                'average_completed' => round($goals_average, 2),
                'completed_per_day' => $goals_per_day,
            ],
        ];
    }
}
