<?php

namespace App\Http\Services;

use App\Http\Interfaces\ReportInterface;
use App\Http\Services\Reports\DayReport;
use App\Http\Services\Reports\MonthReport;
use InvalidArgumentException;

class ReportService
{
    protected array $types = [
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
}
