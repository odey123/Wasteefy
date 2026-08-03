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
        $ipResult = $this->geoLocationService->evaluateIp($request);

        if (! $ipResult['eligible']) {
            return response()->json(['message' => $ipResult['message']], 403);
        }

        $gpsResult = $this->geoLocationService->evaluate($request);

        if (! $gpsResult['eligible']) {
            return response()->json(['message' => $gpsResult['message']], 403);
        }

        return $next($request);
    }
}
