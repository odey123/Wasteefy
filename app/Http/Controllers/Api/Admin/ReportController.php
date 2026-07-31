<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateReportRequest;
use App\Models\Report;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Report::class);

        $reports = Report::query()
            ->with(['reportType', 'photos'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('report_type_id'), fn ($q) => $q->where('report_type_id', $request->integer('report_type_id')))
            ->when($request->filled('city'), fn ($q) => $q->where('city', $request->string('city')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return response()->json($reports);
    }

    public function show(Report $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json($report->load(['reportType', 'photos']));
    }

    public function update(UpdateReportRequest $request, Report $report): JsonResponse
    {
        $report->update($request->validated());

        return response()->json($report->fresh());
    }

    public function destroy(Report $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(status: 204);
    }
}
