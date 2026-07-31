<?php

namespace App\Http\Middleware;

use App\Services\GeoLocationService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRequestOriginatesFromLagos
{
    public function __construct(
        private readonly GeoLocationService $geoLocationService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $location = $this->geoLocationService->locate($request->ip());

        if (! $location) {
            // Local IPs can't be geolocated (there's no public geo record for
            // 127.0.0.1) so we let the request through in local/testing rather
            // than permanently blocking development.
            if (app()->environment(['local', 'testing'])) {
                return $next($request);
            }

            return response()->json([
                'message' => 'We could not verify your location. Please try again.',
            ], 403);
        }

        if ($location['country_code'] !== 'NG') {
            return response()->json([
                'message' => 'Reporting is only available to users within Nigeria.',
            ], 403);
        }

        if ($location['region'] !== 'Lagos') {
            return response()->json([
                'message' => 'Reporting is only available to users within Lagos State.',
            ], 403);
        }

        return $next($request);
    }
}
