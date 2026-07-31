<?php

namespace App\Services;

use Illuminate\Http\Request;

class GeoLocationService
{
    // Approximate bounding box for Lagos State, Nigeria. A rectangle is a
    // coarse approximation of the state's actual shape (it can include a
    // sliver of neighbouring Ogun State at the edges), but GPS coordinates
    // checked against it are far more trustworthy than an IP-based lookup,
    // which doesn't reflect a device's real physical position at all.
    private const LAGOS_MIN_LAT = 6.3529;

    private const LAGOS_MAX_LAT = 6.7027;

    private const LAGOS_MIN_LNG = 2.7005;

    private const LAGOS_MAX_LNG = 4.3174;

    /**
     * Single source of truth for "is this request eligible to report from
     * Lagos, Nigeria" — used by both the enforcing middleware and the
     * informational eligibility endpoint, so the two can never disagree.
     *
     * GPS coordinates are mandatory — there is no IP-based fallback. If the
     * browser hasn't supplied a device location, the request is ineligible.
     *
     * @return array{eligible: bool, message: ?string}
     */
    public function evaluate(Request $request): array
    {
        $lat = $request->input('gps_latitude');
        $lng = $request->input('gps_longitude');

        if (! is_numeric($lat) || ! is_numeric($lng)) {
            return [
                'eligible' => false,
                'message' => 'Location access is required to report an issue.',
            ];
        }

        return $this->isWithinLagos((float) $lat, (float) $lng)
            ? ['eligible' => true, 'message' => null]
            : ['eligible' => false, 'message' => 'Reporting is only available to users within Lagos State.'];
    }

    public function isWithinLagos(float $latitude, float $longitude): bool
    {
        return $latitude >= self::LAGOS_MIN_LAT && $latitude <= self::LAGOS_MAX_LAT
            && $longitude >= self::LAGOS_MIN_LNG && $longitude <= self::LAGOS_MAX_LNG;
    }
}
