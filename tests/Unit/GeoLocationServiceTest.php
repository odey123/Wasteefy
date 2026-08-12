<?php

namespace Tests\Unit;

use App\Services\GeoLocationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GeoLocationServiceTest extends TestCase
{
    /**
     * Real-world coordinates (verified via OpenStreetMap Nominatim) used to
     * confirm the point-in-polygon check matches Lagos State's actual shape
     * — not just a rough bounding box, which would incorrectly pass Ota.
     *
     * @return array<string, array{0: float, 1: float, 2: bool}>
     */
    public static function locationProvider(): array
    {
        return [
            'Lagos Island (Lagos)' => [6.5244, 3.3792, true],
            'Ikorodu (Lagos)' => [6.6191233, 3.5041271, true],
            'Badagry (Lagos, western border)' => [6.4393223, 2.9060324, true],
            'Ota (Ogun State — must be rejected)' => [6.6244986, 3.0822509, false],
            'Sagamu (Ogun State)' => [6.8477165, 3.6440551, false],
            'Abuja (FCT, far away)' => [9.0765, 7.3986, false],
        ];
    }

    #[DataProvider('locationProvider')]
    public function test_is_within_lagos_matches_the_real_state_boundary(float $lat, float $lng, bool $expected): void
    {
        $service = new GeoLocationService;

        $this->assertSame($expected, $service->isWithinLagos($lat, $lng));
    }
}
