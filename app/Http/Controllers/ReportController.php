<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportRequest;
use App\Http\Services\ReportService;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService
    ) {}

    public function index(ReportRequest $request)
    {
        $validated = $request->validated();

        $report = $this->reportService
            ->make($validated['type'])
            ->generate($request->user());

        return response()->json($report);
    }
}
