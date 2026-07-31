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
        $result = $this->geoLocationService->evaluate($request);

        if (! $result['eligible']) {
            return response()->json(['message' => $result['message']], 403);
        }

        return $next($request);
    }
}
