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

    public function eligibility(Request $request): JsonResponse
    {
        $location = $this->geoLocationService->locate($request->ip());

        if (! $location) {
            $eligible = app()->environment(['local', 'testing']);

            return response()->json([
                'eligible' => $eligible,
                'message' => $eligible ? null : 'We could not verify your location.',
            ]);
        }

        if ($location['country_code'] !== 'NG') {
            return response()->json([
                'eligible' => false,
                'message' => 'Reporting is only available to users within Nigeria.',
            ]);
        }

        if ($location['region'] !== 'Lagos') {
            return response()->json([
                'eligible' => false,
                'message' => 'Reporting is only available to users within Lagos State.',
            ]);
        }

        return response()->json(['eligible' => true, 'message' => null]);
    }

    public function store(StoreReportRequest $request): JsonResponse
    {
        if (! $this->recaptchaService->verify($request->validated('recaptcha_token'), $request->ip())) {
            return response()->json([
                'message' => 'reCAPTCHA verification failed. Please try again.',
            ], 422);
        }

        $report = $this->reportService->create(
            $request->safe()->except(['photos', 'recaptcha_token']),
            $request->file('photos', []),
        );

        return response()->json([
            'reference' => $report->reference,
            'message' => 'Report submitted successfully.',
        ], 201);
    }
}
