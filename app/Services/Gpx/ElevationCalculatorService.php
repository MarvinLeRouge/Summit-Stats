<?php

namespace App\Services\Gpx;

use Carbon\Carbon;

class ElevationCalculatorService
{
    private const NOISE_THRESHOLD = 0.5; // mètres

    private const SMOOTHING_WINDOW = 5; // lissage

    /**
     * Calcule la distance totale entre une liste de points GPS via la formule de Haversine.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $points
     * @return float Distance en kilomètres
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
     * Calcule le dénivelé positif cumulé avec lissage par moyenne glissante.
     * Les variations inférieures au seuil de bruit sont ignorées.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $points
     * @return int Dénivelé positif en mètres
     */
    public function elevationGain(array $points): int
    {
        $smoothed = $this->smoothElevations($points);
        $gain = 0.0;

        for ($i = 1; $i < count($smoothed); $i++) {
            if ($smoothed[$i] === null || $smoothed[$i - 1] === null) {
                continue;
            }
            $delta = $smoothed[$i] - $smoothed[$i - 1];
            if ($delta > self::NOISE_THRESHOLD) {
                $gain += $delta;
            }
        }

        return (int) round($gain);
    }

    /**
     * Calcule le dénivelé négatif cumulé avec lissage par moyenne glissante.
     * Les variations inférieures au seuil de bruit sont ignorées.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $points
     * @return int Dénivelé négatif en mètres (valeur positive)
     */
    public function elevationLoss(array $points): int
    {
        $smoothed = $this->smoothElevations($points);
        $loss = 0.0;

        for ($i = 1; $i < count($smoothed); $i++) {
            if ($smoothed[$i] === null || $smoothed[$i - 1] === null) {
                continue;
            }
            $delta = $smoothed[$i - 1] - $smoothed[$i];
            if ($delta > self::NOISE_THRESHOLD) {
                $loss += $delta;
            }
        }

        return (int) round($loss);
    }

    /**
     * Lisse les altitudes par moyenne glissante.
     *
     * @param  array<int, array{ele: float|null}>  $points
     * @return array<int, float|null>
     */
    private function smoothElevations(array $points): array
    {
        $elevations = array_column($points, 'ele');
        $count = count($elevations);

        // Pas de lissage si moins de points que la fenêtre
        if ($count < self::SMOOTHING_WINDOW) {
            return $elevations;
        }

        $smoothed = [];
        $half = (int) (self::SMOOTHING_WINDOW / 2);

        for ($i = 0; $i < $count; $i++) {
            $start = max(0, $i - $half);
            $end = min($count, $i + $half + 1);
            $values = array_filter(
                array_slice($elevations, $start, $end - $start),
                fn ($v) => $v !== null
            );
            $smoothed[$i] = ! empty($values)
                ? array_sum($values) / count($values)
                : null;
        }

        return $smoothed;
    }

    /**
     * Calcule la durée totale entre le premier et le dernier point horodaté.
     * Les pauses sont incluses dans le calcul.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $points
     * @return int Durée totale en secondes, 0 si pas de timestamps
     */
    public function totalDuration(array $points): int
    {
        $first = collect($points)->first(fn ($p) => $p['time'] !== null);
        $last = collect($points)->last(fn ($p) => $p['time'] !== null);

        if (! $first || ! $last || $first === $last) {
            return 0;
        }

        return (int) $first['time']->diffInSeconds($last['time']);
    }

    /**
     * Calcule la durée en mouvement en excluant les pauses dépassant le seuil configuré.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $points
     * @return int Durée en mouvement en secondes
     *
     * @see config('geo.pause_threshold_seconds')
     */
    public function movingDuration(array $points): int
    {
        $moving = 0;

        for ($i = 1; $i < count($points); $i++) {
            if ($points[$i]['time'] === null || $points[$i - 1]['time'] === null) {
                continue;
            }

            $dt = $points[$i - 1]['time']->diffInSeconds($points[$i]['time']);

            if ($dt <= config('geo.pause_threshold_seconds', 30)) {
                $moving += $dt;
            }
        }

        return $moving;
    }

    /**
     * Formule de Haversine — distance en km entre deux points GPS.
     */
    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = config('geo.earth_radius_km');
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }
}
