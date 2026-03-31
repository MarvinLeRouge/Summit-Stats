<?php

namespace App\Services;

use App\Models\Activity;
use App\Services\Gpx\ElevationEnrichmentService;
use App\Services\Gpx\GpxAnalysisOrchestrator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ActivityService
{
    public function __construct(
        private readonly GpxAnalysisOrchestrator $orchestrator,
        private readonly ElevationEnrichmentService $enrichmentService,
    ) {}

    /**
     * Stocke un fichier GPX, analyse la trace et persiste l'activité avec ses segments et points.
     *
     * @param  array{title: string, type: string, environment: string, date: string, comment: string|null}  $metadata
     */
    public function store(array $metadata, UploadedFile $gpxFile, ?callable $onProgress = null): Activity
    {
        $path = 'gpx/'.Str::random(40).'.gpx';
        $absolutePath = Storage::disk('local')->path($path);
        $this->copyGpxFile($gpxFile->getPathname(), $absolutePath);
        $analysis = $this->orchestrator->analyze($absolutePath, $onProgress);

        $activity = Activity::create([
            ...$metadata,
            'gpx_path' => $path,
            ...$analysis['activity_stats'],
        ]);

        foreach ($analysis['segments'] as $segment) {
            $activity->segments()->create($segment);
        }

        $this->persistTrackPoints($activity, $analysis['points']);

        return $activity->load('segments');
    }

    /**
     * Met à jour les métadonnées d'une activité.
     * Si un nouveau fichier GPX est fourni, relance l'analyse complète.
     */
    public function update(Activity $activity, array $metadata, ?UploadedFile $gpxFile = null, ?callable $onProgress = null): Activity
    {
        if ($gpxFile !== null) {
            Storage::disk('local')->delete($activity->gpx_path);
            $path = 'gpx/'.Str::random(40).'.gpx';
            $absolutePath = Storage::disk('local')->path($path);
            $this->copyGpxFile($gpxFile->getPathname(), $absolutePath);
            $analysis = $this->orchestrator->analyze($absolutePath, $onProgress);

            $activity->segments()->delete();
            foreach ($analysis['segments'] as $segment) {
                $activity->segments()->create($segment);
            }

            $activity->trackPoints()->delete();
            $this->persistTrackPoints($activity, $analysis['points']);

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

        $activity->trackPoints()->delete();
        $this->persistTrackPoints($activity, $analysis['points']);

        $activity->update($analysis['activity_stats']);

        return $activity->fresh()->load('segments');
    }

    /**
     * Supprime une activité, ses segments, ses points et son fichier GPX.
     */
    public function destroy(Activity $activity): void
    {
        Storage::disk('local')->delete($activity->gpx_path);
        $activity->delete(); // cascade supprime segments et track_points
    }

    /**
     * Copie un fichier GPX depuis son chemin source vers la destination absolue.
     * Utilise copy() natif pour éviter les problèmes de LOCK_EX de Flysystem sur Docker.
     */
    private function copyGpxFile(string $sourcePath, string $destPath): void
    {
        $dir = dirname($destPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        copy($sourcePath, $destPath);
    }

    /**
     * Persiste les points GPX d'une activité en masse.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: string|null, distance_from_start_km: float}>  $points
     */
    private function persistTrackPoints(Activity $activity, array $points): void
    {
        $now = now();
        $rows = [];

        foreach ($points as $order => $point) {
            $rows[] = [
                'activity_id' => $activity->id,
                'order' => $order,
                'lat' => $point['lat'],
                'lon' => $point['lon'],
                'ele' => $point['ele'] ?? null,
                'time' => isset($point['time']) ? $point['time']->toDateTimeString() : null,
                'distance_from_start_km' => $point['distance_from_start_km'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert en masse par chunks de 500 pour éviter les limites SQLite
        foreach (array_chunk($rows, 500) as $chunk) {
            \DB::table('track_points')->insert($chunk);
        }
    }

    /**
     * Détermine si un fichier GPX nécessite un enrichissement altimétrique.
     */
    public function needsElevationEnrichment(UploadedFile $gpxFile): bool
    {
        $points = $this->orchestrator->parseOnly($gpxFile->getPathname());

        return $this->enrichmentService->needsEnrichment($points);
    }
}
