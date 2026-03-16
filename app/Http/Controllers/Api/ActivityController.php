<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Services\ActivityService;
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

    public function store(StoreActivityRequest $request): JsonResponse
    {
        $activity = $this->activityService->store(
            $request->only('title', 'type', 'environment', 'date', 'comment'),
            $request->file('gpx_file'),
        );

        return $this->created($activity);
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
