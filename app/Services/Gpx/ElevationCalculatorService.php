<?php

namespace App\Services\Gpx;

use Carbon\Carbon;

class ElevationCalculatorService
{
    private const NOISE_THRESHOLD = 2.0; // mètres

    /**
     * Calcule la distance totale en km entre une liste de points (formule de Haversine).
     *
     * @param array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}> $points
     */
    public function totalDistance(array $points): float
    {
        $distance = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $distance += $this->haversine(
                $points[$i - 1]['lat'],
                $points[$i - 1]['lon'],
                $points[$i]['lat'],
                $points[$i]['lon'],
            );
        }

        return $distance;
    }

    /**
     * Calcule le dénivelé positif total (D+) en mètres.
     *
     * @param array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}> $points
     */
    public function elevationGain(array $points): int
    {
        $gain = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            if ($points[$i]['ele'] === null || $points[$i - 1]['ele'] === null) {
                continue;
            }

            $delta = $points[$i]['ele'] - $points[$i - 1]['ele'];

            if ($delta > self::NOISE_THRESHOLD) {
                $gain += $delta;
            }
        }

        return (int) round($gain);
    }

    /**
     * Calcule le dénivelé négatif total (D-) en mètres (valeur positive).
     *
     * @param array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}> $points
     */
    public function elevationLoss(array $points): int
    {
        $loss = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            if ($points[$i]['ele'] === null || $points[$i - 1]['ele'] === null) {
                continue;
            }

            $delta = $points[$i - 1]['ele'] - $points[$i]['ele'];

            if ($delta > self::NOISE_THRESHOLD) {
                $loss += $delta;
            }
        }

        return (int) round($loss);
    }

    /**
     * Calcule la durée totale en secondes à partir des timestamps.
     *
     * @param array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}> $points
     */
    public function totalDuration(array $points): int
    {
        $first = collect($points)->first(fn($p) => $p['time'] !== null);
        $last  = collect($points)->last(fn($p) => $p['time'] !== null);

        if (!$first || !$last || $first === $last) {
            return 0;
        }

        return (int) $first['time']->diffInSeconds($last['time']);
    }

    /**
     * Formule de Haversine — distance en km entre deux points GPS.
     */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }
}