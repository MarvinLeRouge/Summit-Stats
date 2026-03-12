<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Traits\ApiResponse;
use App\Models\Activity;
use App\Services\Gpx\GpxAnalysisOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class ActivityController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        $activities = Activity::query()
            ->when(request('type'), fn($q, $v) => $q->where('type', $v))
            ->when(request('environment'), fn($q, $v) => $q->where('environment', $v))
            ->when(request('date_from'), fn($q, $v) => $q->whereDate('date', '>=', $v))
            ->when(request('date_to'), fn($q, $v) => $q->whereDate('date', '<=', $v))
            ->orderByDesc('date')
            ->paginate(20);

        return $this->success($activities);
    }

    public function store(StoreActivityRequest $request, GpxAnalysisOrchestrator $orchestrator): JsonResponse
    {
        // 1. Stocker le fichier GPX
        $path = $request->file('gpx_file')->store('gpx', 'local');

        // 2. Analyser
        $analysis = $orchestrator->analyze(Storage::disk('local')->path($path));

        // 3. Créer l'activité
        $activity = Activity::create([
            ...$request->only('title', 'type', 'environment', 'date', 'comment'),
            'gpx_path' => $path,
            ...$analysis['activity_stats'],
        ]);

        // 4. Créer les segments
        foreach ($analysis['segments'] as $segment) {
            $activity->segments()->create($segment);
        }

        return $this->created($activity->load('segments'));
    }

    public function show(Activity $activity): JsonResponse
    {
        return $this->success($activity->load('segments'));
    }

    public function update(UpdateActivityRequest $request, Activity $activity, GpxAnalysisOrchestrator $orchestrator): JsonResponse
    {
        // Si nouveau fichier GPX fourni : supprimer l'ancien, relancer l'analyse
        if ($request->hasFile('gpx_file')) {
            Storage::disk('local')->delete($activity->gpx_path);
            $path     = $request->file('gpx_file')->store('gpx', 'local');
            $analysis = $orchestrator->analyze(Storage::disk('local')->path($path));

            $activity->segments()->delete();
            foreach ($analysis['segments'] as $segment) {
                $activity->segments()->create($segment);
            }

            $activity->update([
                ...$request->only('title', 'type', 'environment', 'date', 'comment'),
                'gpx_path' => $path,
                ...$analysis['activity_stats'],
            ]);
        } else {
            $activity->update($request->only('title', 'type', 'environment', 'date', 'comment'));
        }

        return $this->success($activity->fresh()->load('segments'));
    }

    public function destroy(Activity $activity): JsonResponse
    {
        Storage::disk('local')->delete($activity->gpx_path);
        $activity->delete();

        return $this->noContent();
    }
}