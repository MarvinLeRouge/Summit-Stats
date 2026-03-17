<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Services\ActivityService;
use App\Services\Gpx\ElevationEnrichmentService;
use Illuminate\Http\JsonResponse;

class ActivityController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ActivityService $activityService,
    ) {}

    public function index(): JsonResponse
    {
        $activities = Activity::query()
            ->when(request('type'), fn ($q, $v) => $q->where('type', $v))
            ->when(request('environment'), fn ($q, $v) => $q->where('environment', $v))
            ->when(request('date_from'), fn ($q, $v) => $q->whereDate('date', '>=', $v))
            ->when(request('date_to'), fn ($q, $v) => $q->whereDate('date', '<=', $v))
            ->orderByDesc('date')
            ->paginate(20);

        return $this->success($activities);
    }

    public function store(StoreActivityRequest $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->stream(function () use ($request) {
            // Désactiver le timeout
            set_time_limit(0);

            $sendEvent = function (string $event, array $data) {
                echo "event: {$event}\n";
                echo 'data: ' . json_encode($data) . "\n\n";
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            };
            try {
                if (ob_get_level() > 0) {
                    ob_end_clean();
                }
                ini_set('output_buffering', 'off');
                ini_set('zlib.output_compression', false);                
                $sendEvent('status', ['step' => 'parsing']);
                error_log('SSE: parsing sent');

                $needsEnrichment = $this->activityService->needsElevationEnrichment(
                    $request->file('gpx_file')
                );

                if ($needsEnrichment) {
                    $sendEvent('status', ['step' => 'enriching', 'progress' => 0]);
                }

                $activity = $this->activityService->store(
                    $request->only('title', 'type', 'environment', 'date', 'comment'),
                    $request->file('gpx_file'),
                    function (int $percent) use ($sendEvent, $needsEnrichment) {
                        if ($needsEnrichment) {
                            $sendEvent('status', ['step' => 'enriching', 'progress' => $percent]);
                        }
                    }
                );

                $sendEvent('status', ['step' => 'analyzing']);
                error_log('SSE: analyzing sent');
                $sendEvent('done', ['activity' => $activity->toArray()]);
                error_log('SSE: done sent');
                // Forcer la fin du stream
                echo "\n\n";
                if (ob_get_level() > 0) ob_flush();
                flush();
            } catch (\Exception $e) {
                $sendEvent('error', ['message' => $e->getMessage()]);
            }
        }, 200, [
            'Content-Type'                     => 'text/event-stream',
            'Cache-Control'                     => 'no-cache',
            'X-Accel-Buffering'                 => 'no',
            'Connection'                        => 'close', // ← ferme la connexion
        ]);
    }

    public function show(Activity $activity): JsonResponse
    {
        return $this->success($activity->load('segments'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity): JsonResponse
    {
        $updated = $this->activityService->update(
            $activity,
            $request->only('title', 'type', 'environment', 'date', 'comment'),
            $request->file('gpx_file'),
        );

        return $this->success($updated);
    }

    public function destroy(Activity $activity): JsonResponse
    {
        $this->activityService->destroy($activity);

        return $this->noContent();
    }

    public function recalculate(Activity $activity): JsonResponse
    {
        $updated = $this->activityService->recalculate($activity);

        return $this->success($updated);
    }

    public function track(Activity $activity): JsonResponse
    {
        $points = $activity->trackPoints()
            ->select('order', 'lat', 'lon', 'ele', 'distance_from_start_km')
            ->get();

        return $this->success($points);
    }
}
