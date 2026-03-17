<?php

namespace App\Services\Gpx;

use Carbon\Carbon;

class SegmentationService
{
    /**
     * Segmente une liste de points GPS en intervalles homogènes selon le type et la classe de pente.
     * Les intervalles consécutifs de même type et classe sont fusionnés.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $points
     * @return array<int, array{type: string, slope_class: string, order: int, point_index_start: int, point_index_end: int, points: array}>
     */
    public function segment(array $points): array
    {
        $intervals = $this->computeIntervals($points);

        if (empty($intervals)) {
            return [];
        }

        return $this->mergeIntervals($intervals, $points);
    }

    /**
     * Calcule le type et la classe de pente pour chaque intervalle entre points consécutifs.
     */
    private function computeIntervals(array $points): array
    {
        $intervals = [];

        for ($i = 1; $i < count($points); $i++) {
            $prev = $points[$i - 1];
            $curr = $points[$i];

            $distanceM = $this->haversineMeters(
                $prev['lat'], $prev['lon'],
                $curr['lat'], $curr['lon'],
            );

            $eleDelta = ($curr['ele'] ?? 0) - ($prev['ele'] ?? 0);
            $slope = $distanceM > 0 ? ($eleDelta / $distanceM) * 100 : 0;

            $intervals[] = [
                'from' => $i - 1,
                'to' => $i,
                'type' => $this->resolveType($slope),
                'slope_class' => $this->resolveSlopeClass(abs($slope)),
            ];
        }

        return $intervals;
    }

    /**
     * Fusionne les intervalles consécutifs ayant le même type + slope_class en segments.
     */
    private function mergeIntervals(array $intervals, array $points): array
    {
        $segments = [];
        $order = 1;
        $current = $intervals[0];
        $startIndex = $current['from'];

        for ($i = 1; $i < count($intervals); $i++) {
            $interval = $intervals[$i];

            if (
                $interval['type'] === $current['type'] &&
                $interval['slope_class'] === $current['slope_class']
            ) {
                // Même classe — on étend le segment courant
                continue;
            }

            // Changement de classe — on ferme le segment courant
            $segments[] = [
                'type' => $current['type'],
                'slope_class' => $current['slope_class'],
                'order' => $order++,
                'point_index_start' => $startIndex,
                'point_index_end' => $intervals[$i - 1]['to'],
                'points' => array_slice($points, $startIndex, $intervals[$i - 1]['to'] - $startIndex + 1),
            ];

            $current = $interval;
            $startIndex = $interval['from'];
        }

        // Fermer le dernier segment
        $lastInterval = end($intervals);
        $segments[] = [
            'type' => $current['type'],
            'slope_class' => $current['slope_class'],
            'order' => $order,
            'point_index_start' => $startIndex,
            'point_index_end' => $lastInterval['to'],
            'points' => array_slice($points, $startIndex, $lastInterval['to'] - $startIndex + 1),
        ];

        return $segments;
    }

    /**
     * Détermine le type du segment selon la pente (signée).
     */
    private function resolveType(float $slope): string
    {
        $flatThreshold = config('slope_thresholds.classes')[0]['max']; // 5%

        if ($slope >= $flatThreshold) {
            return 'montee';
        }
        if ($slope <= -$flatThreshold) {
            return 'descente';
        }

        return 'plat';
    }

    /**
     * Détermine la classe de pente selon la valeur absolue de la pente.
     */
    private function resolveSlopeClass(float $absSlopePct): string
    {
        $classes = config('slope_thresholds.classes');

        foreach ($classes as $class) {
            $aboveMin = $class['min'] === null || $absSlopePct >= $class['min'];
            $belowMax = $class['max'] === null || $absSlopePct < $class['max'];

            if ($aboveMin && $belowMax) {
                return $class['key'];
            }
        }

        return 'gt35'; // @codeCoverageIgnore
    }

    /**
     * Formule de Haversine — distance en mètres entre deux points GPS.
     */
    private function haversineMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R = config('geo.earth_radius_m');
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $R * asin(sqrt($a));
    }
}
