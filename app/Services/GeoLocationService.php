<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeoLocationService
{
    /**
     * The real, traced boundary of Lagos State (as opposed to a rectangular
     * approximation), loaded once per request. Array of [longitude, latitude]
     * pairs. Source: resources/geo/lagos-boundary.json (OpenStreetMap
     * Nominatim administrative boundary data).
     *
     * @var array<int, array{0: float, 1: float}>|null
     */
    private static ?array $lagosBoundaryRing = null;

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

    /**
     * Checks a coordinate against Lagos State's real, traced boundary using
     * a point-in-polygon (ray casting) test — accurate to the state's actual
     * irregular shape, unlike a bounding-box rectangle which would wrongly
     * include nearby Ogun State towns like Ota at the border.
     */
    public function isWithinLagos(float $latitude, float $longitude): bool
    {
        $ring = $this->lagosBoundaryRing();
        $inside = false;
        $count = count($ring);

        for ($i = 0, $j = $count - 1; $i < $count; $j = $i++) {
            [$xi, $yi] = $ring[$i]; // longitude, latitude
            [$xj, $yj] = $ring[$j];

            $intersects = (($yi > $latitude) !== ($yj > $latitude))
                && ($longitude < ($xj - $xi) * ($latitude - $yi) / ($yj - $yi) + $xi);

            if ($intersects) {
                $inside = ! $inside;
            }
        }

        return $inside;
    }

    /**
     * @return array<int, array{0: float, 1: float}>
     */
    private function lagosBoundaryRing(): array
    {
        if (self::$lagosBoundaryRing === null) {
            $data = json_decode(file_get_contents(resource_path('geo/lagos-boundary.json')), true);
            self::$lagosBoundaryRing = $data['ring'];
        }

        return self::$lagosBoundaryRing;
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
