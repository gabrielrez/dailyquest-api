<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Http\Services\ReportService;
use App\Models\Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    /**
     * Get all auto-generated reports for the authenticated user.
     */
    public function index(Request $request)
    {
        return $this->respond(
            $this->reportService->filter($request)
        );
    }

    public function generate(ReportRequest $request)
    {
        $validated = $request->validated();

        $report = $this->reportService
            ->make($validated['type'])
            ->generate($request->user());

        return $this->respond($report);
    }

    public function queueReport(ReportRequest $request)
    {
        $type = $request->validated()['type'];

        $this->reportService->resolveGenerateReport($request->user(), $type);

        return $this->respond('Report Queued');
    }
}
