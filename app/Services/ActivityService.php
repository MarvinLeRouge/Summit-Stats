<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\Gpx\GpxAnalysisOrchestrator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ActivityService
{
    public function __construct(
        private readonly GpxAnalysisOrchestrator $orchestrator,
    ) {}

    /**
     * Stocke un fichier GPX, analyse la trace et persiste l'activité avec ses segments.
     *
     * @param  array{title: string, type: string, environment: string, date: string, comment: string|null}  $metadata
     */
    public function store(array $metadata, UploadedFile $gpxFile): Activity
    {
        $path = $gpxFile->store('gpx', 'local');
        $analysis = $this->orchestrator->analyze(Storage::disk('local')->path($path));

        $activity = Activity::create([
            ...$metadata,
            'gpx_path' => $path,
            ...$analysis['activity_stats'],
        ]);

        foreach ($analysis['segments'] as $segment) {
            $activity->segments()->create($segment);
        }

        return $activity->load('segments');
    }

    /**
     * Met à jour les métadonnées d'une activité.
     * Si un nouveau fichier GPX est fourni, relance l'analyse complète.
     */
    public function update(Activity $activity, array $metadata, ?UploadedFile $gpxFile = null): Activity
    {
        if ($gpxFile !== null) {
            Storage::disk('local')->delete($activity->gpx_path);
            $path = $gpxFile->store('gpx', 'local');
            $analysis = $this->orchestrator->analyze(Storage::disk('local')->path($path));

            $activity->segments()->delete();
            foreach ($analysis['segments'] as $segment) {
                $activity->segments()->create($segment);
            }

            $activity->update([
                ...$metadata,
                'gpx_path' => $path,
                ...$analysis['activity_stats'],
            ]);
        } else {
            $activity->update($metadata);
        }

        return $activity->fresh()->load('segments');
    }

    /**
     * Recalcule les stats d'une activité depuis son fichier GPX brut.
     */
    public function recalculate(Activity $activity): Activity
    {
        $path = Storage::disk('local')->path($activity->gpx_path);
        $analysis = $this->orchestrator->analyze($path);

        $activity->segments()->delete();
        foreach ($analysis['segments'] as $segment) {
            $activity->segments()->create($segment);
        }

        $activity->update($analysis['activity_stats']);

        return $activity->fresh()->load('segments');
    }

    /**
     * Supprime une activité, ses segments et son fichier GPX.
     */
    public function destroy(Activity $activity): void
    {
        Storage::disk('local')->delete($activity->gpx_path);
        $activity->delete();
    }
}
