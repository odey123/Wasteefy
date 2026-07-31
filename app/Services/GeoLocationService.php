<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeoLocationService
{
    /**
     * Resolve an IP to a country code + region name.
     * Returns null when the IP can't be geolocated (private/loopback IP,
     * or the lookup provider failed) so callers can decide how to treat that.
     *
     * @return array{country_code: string, region: string}|null
     */
    public function locate(string $ip): ?array
    {
        if ($this->isPrivateOrReservedIp($ip)) {
            return null;
        }

        $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}", [
            'fields' => 'status,countryCode,regionName',
        ]);

        if (! $response->ok() || $response->json('status') !== 'success') {
            return null;
        }

        return [
            'country_code' => $response->json('countryCode'),
            'region' => $response->json('regionName'),
        ];
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }
}
