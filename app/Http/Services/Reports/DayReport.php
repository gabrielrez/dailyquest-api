<?php

namespace App\Http\Services\Reports;

use App\Http\Interfaces\ReportInterface;
use App\Models\User;
use Carbon\Carbon;

class DayReport implements ReportInterface
{
    public function generate(User $user): array
    {
        $now = Carbon::now();
        $start = $now->copy()->startOfDay();
        $end = $now->copy()->endOfDay();

        $collections = $user->ownedCollections()
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$start, $end])
            ->get(['id', 'completed_at']);

        $goals = $user->goals()
            ->where('status', 'done')
            ->whereBetween('done_at', [$start, $end])
            ->get(['id', 'done_at']);

        return [
            'month' => $now->format('D - Y-m-d'),
            'collections' => [
                'total_completed' => $collections->count()
            ],
            'goals' => [
                'total_completed' => $goals->count()
            ],
        ];
    }
}
