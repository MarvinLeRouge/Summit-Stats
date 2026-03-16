<?php

namespace App\Services\Gpx;

use Carbon\Carbon;

class StatsAggregatorService
{
    /**
     * Agrège les stats de chaque segment et calcule les stats globales de l'activité.
     *
     * @param  array<int, array{type: string, slope_class: string, order: int, points: array}>  $segments  Segments issus du SegmentationService
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: Carbon|null}>  $allPoints  Points bruts pour le calcul de la durée en mouvement
     * @return array{activity_stats: array, segments: array}
     */
    public function aggregate(array $segments, array $allPoints = []): array
    {
        $aggregatedSegments = [];

        foreach ($segments as $index => $segment) {
            $aggregatedSegments[] = $this->aggregateSegment($segment, $index + 1);
        }

        return [
            'activity_stats' => $this->computeActivityStats($aggregatedSegments, $allPoints),
            'segments' => $aggregatedSegments,
        ];
    }

    /**
     * Calcule les stats d'un segment individuel.
     */
    private function aggregateSegment(array $segment, int $order): array
    {
        $points = $segment['points'];
        $distanceKm = $this->totalDistance($points);
        $elevationDelta = $this->elevationDelta($points);
        $durationSeconds = $this->totalDuration($points);
        $avgSpeedKmh = $durationSeconds > 0
            ? round($distanceKm / ($durationSeconds / 3600), 2)
            : null;

        $avgSlopePct = $distanceKm > 0
            ? round($elevationDelta / ($distanceKm * 1000) * 100, 2)
            : 0.0;

        $avgAscentSpeedMh = null;
        if ($segment['type'] === 'montee' && $durationSeconds > 0 && $elevationDelta > 0) {
            $avgAscentSpeedMh = round($elevationDelta / ($durationSeconds / 3600), 2);
        }

        return [
            'type' => $segment['type'],
            'slope_class' => $segment['slope_class'],
            'order' => $order,
            'point_index_start' => $segment['point_index_start'],
            'point_index_end' => $segment['point_index_end'],
            'distance_km' => round($distanceKm, 4),
            'elevation_delta' => $elevationDelta,
            'duration_seconds' => $durationSeconds,
            'avg_speed_kmh' => $avgSpeedKmh,
            'avg_slope_pct' => $avgSlopePct,
            'avg_ascent_speed_mh' => $avgAscentSpeedMh,
        ];
    }

    /**
     * Calcule les stats globales à partir des segments agrégés.
     */
    private function computeActivityStats(array $segments, array $allPoints): array
    {
        $totalDistance = array_sum(array_column($segments, 'distance_km'));
        $totalDuration = array_sum(array_column($segments, 'duration_seconds'));

        // Dénivelés
        $elevationGain = array_sum(array_filter(array_column($segments, 'elevation_delta'), fn ($d) => $d > 0));
        $elevationLoss = abs(array_sum(array_filter(array_column($segments, 'elevation_delta'), fn ($d) => $d < 0)));

        // Vitesse moyenne totale
        $avgSpeedKmh = $totalDuration > 0
            ? round($totalDistance / ($totalDuration / 3600), 2)
            : null;

        // Vitesse moyenne en mouvement
        $movingDuration = $this->computeMovingDuration($allPoints);
        $avgSpeedMovingKmh = $movingDuration > 0
            ? round($totalDistance / ($movingDuration / 3600), 2)
            : null;

        // Segments par type
        $ascentSegments = array_values(array_filter($segments, fn ($s) => $s['type'] === 'montee'));
        $descentSegments = array_values(array_filter($segments, fn ($s) => $s['type'] === 'descente'));
        $flatSegments = array_values(array_filter($segments, fn ($s) => $s['type'] === 'plat'));

        // Vitesse ascensionnelle moyenne (segments montants)
        $avgAscentSpeedMh = $this->ascentSpeed($ascentSegments);

        // Vitesse ascensionnelle départ → sommet
        $summitAscentSpeedMh = $this->summitAscentSpeed($segments);

        // Vitesse ascensionnelle plus long tronçon non descendant
        $longestAscent = $this->longestNonDescentSpeed($segments);

        // Vitesses à plat et en descente
        $avgFlatSpeedKmh = $this->avgSpeed($flatSegments);
        $avgDescentSpeedKmh = $this->avgSpeed($descentSegments);
        $avgDescentRateMh = $this->descentRate($descentSegments);

        // Répartition globale
        $pctAscent = $totalDistance > 0 ? round(array_sum(array_column($ascentSegments, 'distance_km')) / $totalDistance * 100, 1) : 0;
        $pctDescent = $totalDistance > 0 ? round(array_sum(array_column($descentSegments, 'distance_km')) / $totalDistance * 100, 1) : 0;
        $pctFlat = $totalDistance > 0 ? round(array_sum(array_column($flatSegments, 'distance_km')) / $totalDistance * 100, 1) : 0;

        // Répartition par classe de pente
        $slopeClasses = ['lt5', '5_15', '15_25', '25_35', 'gt35'];
        $pctBySlopeAscent = $this->pctBySlopeClass($ascentSegments, $totalDistance, $slopeClasses, 'pct_ascent');
        $pctBySlopeDescent = $this->pctBySlopeClass($descentSegments, $totalDistance, $slopeClasses, 'pct_descent');

        return array_merge([
            'distance_km' => round($totalDistance, 4),
            'elevation_gain' => (int) $elevationGain,
            'elevation_loss' => (int) $elevationLoss,
            'duration_seconds' => $totalDuration,
            'moving_duration_seconds' => $movingDuration,
            'avg_speed_kmh' => $avgSpeedKmh,
            'avg_speed_moving_kmh' => $avgSpeedMovingKmh,
            'avg_ascent_speed_mh' => $avgAscentSpeedMh,
            'summit_ascent_speed_mh' => $summitAscentSpeedMh,
            'longest_ascent_speed_mh' => $longestAscent['speed'],
            'longest_ascent_distance_km' => $longestAscent['distance_km'],
            'avg_flat_speed_kmh' => $avgFlatSpeedKmh,
            'avg_descent_speed_kmh' => $avgDescentSpeedKmh,
            'avg_descent_rate_mh' => $avgDescentRateMh,
            'pct_ascent' => $pctAscent,
            'pct_flat' => $pctFlat,
            'pct_descent' => $pctDescent,
        ], $pctBySlopeAscent, $pctBySlopeDescent);
    }

    /**
     * Vitesse ascensionnelle moyenne sur les segments montants.
     */
    private function ascentSpeed(array $ascentSegments): ?float
    {
        $totalD = array_sum(array_filter(array_column($ascentSegments, 'elevation_delta'), fn ($d) => $d > 0));
        $totalDuration = array_sum(array_column($ascentSegments, 'duration_seconds'));

        return $totalDuration > 0 ? round($totalD / ($totalDuration / 3600), 2) : null;
    }

    /**
     * Vitesse ascensionnelle départ → point culminant.
     */
    private function summitAscentSpeed(array $segments): ?float
    {
        // Trouver le segment contenant le point culminant
        $maxEle = null;
        $summitOrderIdx = null;

        foreach ($segments as $idx => $segment) {
            // On approxime : le sommet est dans le dernier segment de montée avant une descente
            if ($segment['elevation_delta'] > 0) {
                $summitOrderIdx = $idx;
            }
        }

        if ($summitOrderIdx === null) {
            return null;
        }

        // Agréger du début jusqu'au segment sommet inclus
        $relevantSegments = array_slice($segments, 0, $summitOrderIdx + 1);
        $dPlus = array_sum(array_filter(array_column($relevantSegments, 'elevation_delta'), fn ($d) => $d > 0));
        $duration = array_sum(array_column($relevantSegments, 'duration_seconds'));

        return $duration > 0 && $dPlus > 0 ? round($dPlus / ($duration / 3600), 2) : null;
    }

    /**
     * Vitesse ascensionnelle et distance sur le plus long tronçon non descendant
     * (séquence de segments sans elevation_delta négatif).
     */
    private function longestNonDescentSpeed(array $segments): array
    {
        $bestDuration = 0;
        $bestDPlus = 0;
        $bestDistanceKm = 0;
        $currentSegs = [];

        foreach ($segments as $segment) {
            if ($segment['elevation_delta'] >= 0) {
                $currentSegs[] = $segment;
            } else {
                $this->evaluateTroncon($currentSegs, $bestDuration, $bestDPlus, $bestDistanceKm);
                $currentSegs = [];
            }
        }

        $this->evaluateTroncon($currentSegs, $bestDuration, $bestDPlus, $bestDistanceKm);

        return [
            'speed' => $bestDuration > 0 && $bestDPlus > 0
                ? round($bestDPlus / ($bestDuration / 3600), 2)
                : null,
            'distance_km' => $bestDistanceKm > 0
                ? round($bestDistanceKm, 3)
                : null,
        ];
    }

    /**
     * Évalue un tronçon et met à jour le meilleur si plus long.
     */
    private function evaluateTroncon(array $segs, int &$bestDuration, float &$bestDPlus, float &$bestDistanceKm): void
    {
        if (empty($segs)) {
            return;
        }

        $duration = array_sum(array_column($segs, 'duration_seconds'));
        $dPlus = array_sum(array_filter(array_column($segs, 'elevation_delta'), fn ($d) => $d > 0));
        $distanceKm = array_sum(array_column($segs, 'distance_km'));

        if ($duration > $bestDuration) {
            $bestDuration = $duration;
            $bestDPlus = $dPlus;
            $bestDistanceKm = $distanceKm;
        }
    }

    /**
     * Vitesse moyenne sur un ensemble de segments.
     */
    private function avgSpeed(array $segments): ?float
    {
        $distance = array_sum(array_column($segments, 'distance_km'));
        $duration = array_sum(array_column($segments, 'duration_seconds'));

        return $duration > 0 && $distance > 0
            ? round($distance / ($duration / 3600), 2)
            : null;
    }

    /**
     * Vitesse descensionnelle (D- / durée des segments descendants).
     */
    private function descentRate(array $descentSegments): ?float
    {
        $totalDMinus = abs(array_sum(array_filter(array_column($descentSegments, 'elevation_delta'), fn ($d) => $d < 0)));
        $totalDuration = array_sum(array_column($descentSegments, 'duration_seconds'));

        return $totalDuration > 0 && $totalDMinus > 0
            ? round($totalDMinus / ($totalDuration / 3600), 2)
            : null;
    }

    /**
     * Calcule les % de distance par classe de pente sur la distance totale.
     */
    private function pctBySlopeClass(array $segments, float $totalDistance, array $classes, string $prefix): array
    {
        $result = [];

        foreach ($classes as $class) {
            $key = "{$prefix}_{$class}";
            $distance = array_sum(
                array_column(
                    array_filter($segments, fn ($s) => $s['slope_class'] === $class),
                    'distance_km'
                )
            );
            $result[$key] = $totalDistance > 0 ? round($distance / $totalDistance * 100, 1) : 0.0;
        }

        return $result;
    }

    /**
     * Durée en mouvement calculée sur les points bruts.
     */
    private function computeMovingDuration(array $allPoints): int
    {
        if (empty($allPoints)) {
            return 0;
        }

        $moving = 0;
        $pauseThreshold = config('geo.pause_threshold_seconds', 30);

        for ($i = 1; $i < count($allPoints); $i++) {
            if ($allPoints[$i]['time'] === null || $allPoints[$i - 1]['time'] === null) {
                continue;
            }

            $dt = $allPoints[$i - 1]['time']->diffInSeconds($allPoints[$i]['time']);
            if ($dt <= $pauseThreshold) {
                $moving += $dt;
            }
        }

        return $moving;
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
                $points[$i]['lat'], $points[$i]['lon'],
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
        $last = $points[count($points) - 1]['ele'] ?? 0;

        return (int) round($last - $first);
    }

    /**
     * Durée totale en secondes.
     */
    private function totalDuration(array $points): int
    {
        $first = collect($points)->first(fn ($p) => $p['time'] !== null);
        $last = collect($points)->last(fn ($p) => $p['time'] !== null);

        if (! $first || ! $last || $first === $last) {
            return 0;
        }

        return (int) $first['time']->diffInSeconds($last['time']);
    }

    /**
     * Formule de Haversine — distance en km.
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
