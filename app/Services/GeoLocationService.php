<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

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
     * Cheap first filter: is this request even coming from Nigeria, based on
     * IP address? No permission prompt needed, so a frontend can use this to
     * decide whether the report button is clickable at all, before ever
     * asking the browser for GPS. This is NOT a substitute for evaluate() —
     * IP location is coarse and spoofable; it only gates the UI, while the
     * GPS check remains the real enforcement on submission.
     *
     * @return array{eligible: bool, message: ?string}
     */
    public function evaluateIp(Request $request): array
    {
        $ip = $request->ip();

        if ($this->isPrivateOrReservedIp($ip)) {
            $eligible = app()->environment(['local', 'testing']);

            return [
                'eligible' => $eligible,
                'message' => $eligible ? null : 'We could not verify your location.',
            ];
        }

        $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
            'fields' => 'status,countryCode',
        ]);

        if (! $response->ok() || $response->json('status') !== 'success') {
            return ['eligible' => false, 'message' => 'We could not verify your location.'];
        }

        return $response->json('countryCode') === 'NG'
            ? ['eligible' => true, 'message' => null]
            : ['eligible' => false, 'message' => 'Reporting is only available to users within Nigeria.'];
    }

    /**
     * Single source of truth for "is this request eligible to report from
     * Lagos, Nigeria" via GPS — used by both the enforcing middleware and
     * the informational eligibility endpoint, so the two can never disagree.
     *
     * GPS coordinates are mandatory — there is no IP-based fallback for this
     * check specifically. If the browser hasn't supplied a device location,
     * the request is ineligible.
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

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
