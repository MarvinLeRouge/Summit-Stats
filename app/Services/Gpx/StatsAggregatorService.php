<?php

namespace App\Services\Gpx;

use Carbon\Carbon;

class StatsAggregatorService
{
    /**
     * Agrège les stats de chaque segment et calcule les stats globales de l'activité.
     *
     * @param  array<int, array{type: string, slope_class: string, order: int, points: array}> $segments
     * @return array{activity_stats: array, segments: array}
     */
    public function aggregate(array $segments): array
    {
        $aggregatedSegments = [];

        foreach ($segments as $index => $segment) {
            $aggregatedSegments[] = $this->aggregateSegment($segment, $index + 1);
        }

        return [
            'activity_stats' => $this->computeActivityStats($aggregatedSegments),
            'segments'       => $aggregatedSegments,
        ];
    }

    /**
     * Calcule les stats d'un segment individuel.
     */
    private function aggregateSegment(array $segment, int $order): array
    {
        $points          = $segment['points'];
        $distanceKm      = $this->totalDistance($points);
        $elevationDelta  = $this->elevationDelta($points);
        $durationSeconds = $this->totalDuration($points);
        $avgSpeedKmh     = $durationSeconds > 0
            ? round($distanceKm / ($durationSeconds / 3600), 2)
            : 0.0;

        $avgSlopePct = $distanceKm > 0
            ? round($elevationDelta / ($distanceKm * 1000) * 100, 2)
            : 0.0;

        $avgAscentSpeedMh = null;
        if ($segment['type'] === 'montee' && $durationSeconds > 0) {
            $avgAscentSpeedMh = round($elevationDelta / ($durationSeconds / 3600), 2);
        }

        return [
            'type'              => $segment['type'],
            'slope_class'       => $segment['slope_class'],
            'order'             => $order,
            'point_index_start' => $segment['point_index_start'],
            'point_index_end'   => $segment['point_index_end'],
            'distance_km'       => round($distanceKm, 4),
            'elevation_delta'   => $elevationDelta,
            'duration_seconds'  => $durationSeconds,
            'avg_speed_kmh'     => $avgSpeedKmh,
            'avg_slope_pct'     => $avgSlopePct,
            'avg_ascent_speed_mh' => $avgAscentSpeedMh,
        ];
    }

    /**
     * Calcule les stats globales à partir des segments agrégés.
     */
    private function computeActivityStats(array $segments): array
    {
        $totalDistanceKm   = array_sum(array_column($segments, 'distance_km'));
        $totalDuration     = array_sum(array_column($segments, 'duration_seconds'));
        $elevationGain     = array_sum(array_filter(
            array_column($segments, 'elevation_delta'),
            fn($d) => $d > 0
        ));
        $elevationLoss     = abs(array_sum(array_filter(
            array_column($segments, 'elevation_delta'),
            fn($d) => $d < 0
        )));

        $avgSpeedKmh = $totalDuration > 0
            ? round($totalDistanceKm / ($totalDuration / 3600), 2)
            : 0.0;

        // Vitesse ascensionnelle moyenne sur les segments de montée uniquement
        $ascentSegments = array_filter($segments, fn($s) => $s['type'] === 'montee');
        $totalAscentD   = array_sum(array_filter(
            array_column(array_values($ascentSegments), 'elevation_delta'),
            fn($d) => $d > 0
        ));
        $totalAscentDuration = array_sum(array_column(array_values($ascentSegments), 'duration_seconds'));
        $avgAscentSpeedMh = $totalAscentDuration > 0
            ? round($totalAscentD / ($totalAscentDuration / 3600), 2)
            : null;

        return [
            'distance_km'          => round($totalDistanceKm, 4),
            'elevation_gain'       => (int) $elevationGain,
            'elevation_loss'       => (int) $elevationLoss,
            'duration_seconds'     => $totalDuration,
            'avg_speed_kmh'        => $avgSpeedKmh,
            'avg_ascent_speed_mh'  => $avgAscentSpeedMh,
        ];
    }

    /**
     * Distance totale en km entre une liste de points (Haversine).
     */
    private function totalDistance(array $points): float
    {
        $distance = 0.0;

        for ($i = 1; $i < count($points); $i++) {
            $distance += $this->haversine(
                $points[$i - 1]['lat'], $points[$i - 1]['lon'],
                $points[$i]['lat'],     $points[$i]['lon'],
            );
        }

        return $distance;
    }

    /**
     * Dénivelé total du segment (positif = montée, négatif = descente).
     */
    private function elevationDelta(array $points): int
    {
        $first = $points[0]['ele'] ?? 0;
        $last  = $points[count($points) - 1]['ele'] ?? 0;

        return (int) round($last - $first);
    }

    /**
     * Durée totale en secondes.
     */
    private function totalDuration(array $points): int
    {
        $first = collect($points)->first(fn($p) => $p['time'] !== null);
        $last  = collect($points)->last(fn($p) => $p['time'] !== null);

        if (!$first || !$last || $first === $last) {
            return 0;
        }

        return (int) $first['time']->diffInSeconds($last['time']);
    }

    /**
     * Formule de Haversine — distance en km.
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