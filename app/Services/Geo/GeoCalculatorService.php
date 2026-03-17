<?php

namespace App\Services\Geo;

class GeoCalculatorService
{
    /**
     * Calcule la distance en kilomètres entre deux points GPS via la formule de Haversine.
     *
     * @param  float  $lat1  Latitude du point 1
     * @param  float  $lon1  Longitude du point 1
     * @param  float  $lat2  Latitude du point 2
     * @param  float  $lon2  Longitude du point 2
     * @return float Distance en kilomètres
     */
    public function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = config('geo.earth_radius_km');
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }

    /**
     * Calcule la distance en mètres entre deux points GPS via la formule de Haversine.
     *
     * @param  float  $lat1  Latitude du point 1
     * @param  float  $lon1  Longitude du point 1
     * @param  float  $lat2  Latitude du point 2
     * @param  float  $lon2  Longitude du point 2
     * @return float Distance en mètres
     */
    public function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        return $this->haversineKm($lat1, $lon1, $lat2, $lon2) * 1000;
    }
}
