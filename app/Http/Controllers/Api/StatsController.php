<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StatsRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Models\Segment;
use Illuminate\Http\JsonResponse;

class StatsController extends Controller
{
    use ApiResponse;

    private const METRIC_UNITS = [
        'avg_speed_kmh'         => 'km/h',
        'avg_speed_moving_kmh'  => 'km/h',
        'avg_flat_speed_kmh'    => 'km/h',
        'avg_descent_speed_kmh' => 'km/h',
        'avg_descent_rate_mh'   => 'm/h',
        'avg_ascent_speed_mh'   => 'm/h',
        'elevation_gain'        => 'm',
        'distance_km'           => 'km',
    ];

    private const ACTIVITY_LEVEL_METRICS = [
        'avg_speed_kmh',
        'avg_speed_moving_kmh',
        'avg_flat_speed_kmh',
        'avg_descent_speed_kmh',
        'avg_descent_rate_mh',
    ];

    private const SEGMENT_LEVEL_METRICS = [
        'avg_ascent_speed_mh',
    ];
    public function index(StatsRequest $request): JsonResponse
    {
        $metric = $request->input('metric');

        // Récupérer les activités filtrées
        $activityIds = Activity::query()
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->when($request->environment, fn ($q, $v) => $q->where('environment', $v))
            ->when($request->activity_id, fn ($q, $v) => $q->where('id', $v))
            ->when($request->date_from, fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when($request->date_to, fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->orderBy('date')
            ->pluck('id', 'title')
            ->toArray();

        if (empty($activityIds)) {
            return $this->success([], 200)->header('X-Meta', json_encode([
                'metric' => $metric,
                'unit' => self::METRIC_UNITS[$metric] ?? '',
                'count' => 0,
            ]));
        }

        // Construire les données selon la métrique
        $data = match (true) {
            in_array($metric, self::SEGMENT_LEVEL_METRICS) => $this->getSegmentMetric($request, $activityIds, $metric),
            default => $this->getActivityMetric($request, $activityIds, $metric),
        };

        return response()->json([
            'success' => true,
            'data' => $data,
            'meta' => [
                'metric' => $metric,
                'unit' => self::METRIC_UNITS[$metric] ?? '',
                'count' => count($data),
            ],
        ]);
    }

    /**
     * Métriques calculées sur les segments (vitesses).
     */
    private function getSegmentMetric(StatsRequest $request, array $activityIds, string $metric): array
    {
        $segments = Segment::query()
            ->whereIn('activity_id', array_values($activityIds))
            ->when($request->slope_class, fn ($q, $v) => $q->where('slope_class', $v))
            ->when($request->slope_min !== null, fn ($q) => $q->where('avg_slope_pct', '>=', $request->slope_min))
            ->when($request->slope_max !== null, fn ($q) => $q->where('avg_slope_pct', '<=', $request->slope_max))
            ->whereNotNull($metric)
            ->with('activity:id,title,date')
            ->get();

        // Agréger par activité : moyenne de la métrique sur les segments filtrés
        return $segments
            ->groupBy('activity_id')
            ->map(function ($segs) use ($metric) {
                $activity = $segs->first()->activity;

                return [
                    'date' => $activity->date->format('Y-m-d'),
                    'value' => round($segs->avg($metric), 2),
                    'activity_title' => $activity->title,
                ];
            })
            ->values()
            ->sortBy('date')
            ->values()
            ->toArray();
    }

    /**
     * Métriques calculées sur les activités (D+, distance).
     */
    private function getActivityMetric(StatsRequest $request, array $activityIds, string $metric): array
    {
        return Activity::query()
            ->whereIn('id', array_values($activityIds))
            ->when($request->slope_min || $request->slope_max || $request->slope_class, function ($q) use ($request) {
                // Filtrer les activités qui ont au moins un segment dans l'intervalle de pente
                $q->whereHas('segments', function ($sq) use ($request) {
                    $sq->when($request->slope_class, fn ($q, $v) => $q->where('slope_class', $v))
                        ->when($request->slope_min, fn ($q, $v) => $q->where('avg_slope_pct', '>=', $v))
                        ->when($request->slope_max, fn ($q, $v) => $q->where('avg_slope_pct', '<=', $v));
                });
            })
            ->orderBy('date')
            ->get()
            ->map(fn ($activity) => [
                'date' => $activity->date->format('Y-m-d'),
                'value' => $activity->{$metric},
                'activity_title' => $activity->title,
            ])
            ->toArray();
    }
}
