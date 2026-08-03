<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReportRequest;
use App\Services\GeoLocationService;
use App\Services\RecaptchaService;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly GeoLocationService $geoLocationService,
        private readonly RecaptchaService $recaptchaService,
    ) {}

    public function ipEligibility(Request $request): JsonResponse
    {
        return response()->json($this->geoLocationService->evaluateIp($request));
    }

    public function eligibility(Request $request): JsonResponse
    {
        return response()->json($this->geoLocationService->evaluate($request));
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        if (! $this->recaptchaService->verify($request->validated('recaptcha_token'), $request->ip())) {
            return response()->json([
                'message' => 'reCAPTCHA verification failed. Please try again.',
            ], 422);
        }

        $data = $request->safe()->except(['photos', 'recaptcha_token', 'gps_latitude', 'gps_longitude']);
        $data['latitude'] = $request->validated('gps_latitude');
        $data['longitude'] = $request->validated('gps_longitude');

        $report = $this->reportService->create($data, $request->file('photos', []));

        return response()->json([
            'reference' => $report->reference,
            'message' => 'Report submitted successfully.',
        ], 201);
    }
}
