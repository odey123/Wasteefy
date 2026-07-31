<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReportType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ReportTypeController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(ReportType::query()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['slug'] = Str::slug($data['name']);

        $reportType = ReportType::create($data);

        return response()->json($reportType, 201);
    }

    public function update(Request $request, ReportType $reportType): JsonResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $reportType->update($data);

        return response()->json($reportType->fresh());
    }

    public function destroy(ReportType $reportType): JsonResponse
    {
        $reportType->delete();

        return response()->json(status: 204);
    }
}
