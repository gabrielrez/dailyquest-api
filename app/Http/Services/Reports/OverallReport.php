<?php

namespace App\Http\Services\Reports;

use App\Http\Interfaces\ReportInterface;
use App\Models\User;
use Carbon\Carbon;

class OverallReport implements ReportInterface
{
    public function generate(User $user): array
    {
        $collections = $user->ownedCollections()
            ->whereNotNull('completed_at')
            ->get(['id', 'completed_at']);

        $goals = $user->goals()
            ->where('status', 'done')
            ->get(['id', 'done_at']);

        return [
            'overall' => Carbon::now()->format('Y-m-d'),
            'collections' => [
                'total_completed' => $collections->count(),
            ],
            'goals' => [
                'total_completed' => $goals->count(),
            ],
        ];
    }
}
