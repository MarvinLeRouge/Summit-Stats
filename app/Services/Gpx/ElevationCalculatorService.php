<?php

namespace App\Services\Gpx;

use Carbon\Carbon;

class ElevationCalculatorService
{
    private const NOISE_THRESHOLD = 0.5;
    private const SMOOTHING_WINDOW = 5;

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
        $smoothed = $this->smoothElevations($points);
        $gain     = 0.0;

        for ($i = 1; $i < count($smoothed); $i++) {
            if ($smoothed[$i] === null || $smoothed[$i - 1] === null) continue;
            $delta = $smoothed[$i] - $smoothed[$i - 1];
            if ($delta > self::NOISE_THRESHOLD) $gain += $delta;
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
        $smoothed = $this->smoothElevations($points);
        $loss     = 0.0;

        for ($i = 1; $i < count($smoothed); $i++) {
            if ($smoothed[$i] === null || $smoothed[$i - 1] === null) continue;
            $delta = $smoothed[$i - 1] - $smoothed[$i];
            if ($delta > self::NOISE_THRESHOLD) $loss += $delta;
        }

        return (int) round($loss);
    }

    /**
     * Lisse les altitudes par moyenne glissante.
     *
     * @param  array<int, array{ele: float|null}> $points
     * @return array<int, float|null>
     */
    private function smoothElevations(array $points): array
    {
        $elevations = array_column($points, 'ele');
        $count      = count($elevations);

        // Pas de lissage si moins de points que la fenêtre
        if ($count < self::SMOOTHING_WINDOW) {
            return $elevations;
        }

        $smoothed   = [];
        $half       = (int) (self::SMOOTHING_WINDOW / 2);

        for ($i = 0; $i < $count; $i++) {
            $start  = max(0, $i - $half);
            $end    = min($count, $i + $half + 1);
            $values = array_filter(
                array_slice($elevations, $start, $end - $start),
                fn($v) => $v !== null
            );
            $smoothed[$i] = !empty($values)
                ? array_sum($values) / count($values)
                : null;
        }

        return $smoothed;
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
        $R    = config('geo.earth_radius_km');
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }
}