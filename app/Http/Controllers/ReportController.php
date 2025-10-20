<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Http\Services\ReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function generate(ReportRequest $request)
    {
        $validated = $request->validated();

        $report = $this->reportService
            ->make($validated['type'])
            ->generate($request->user());

        return $this->respond($report);
    }

    public function queueReport(Request $request)
    {
        $type = $request->input('type') ?? 'month';

        $this->reportService->resolveGenerateReport($request->user(), $type);

        return $this->respond('Report Queued');
    }
}
