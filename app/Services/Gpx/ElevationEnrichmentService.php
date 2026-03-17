<?php

namespace App\Services\Gpx;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ElevationEnrichmentService
{
    /**
     * Enrichit les points GPS avec les altitudes manquantes via OpenTopoData.
     * Si le service est désactivé ou en erreur, retourne les points inchangés.
     *
     * @param  array<int, array{lat: float, lon: float, ele: float|null, time: mixed}>  $points
     * @param  callable|null  $onProgress  Callback appelé après chaque chunk : fn(int $percent) => void
     * @return array<int, array{lat: float, lon: float, ele: float|null, time: mixed}>
     */
    public function enrich(array $points, ?callable $onProgress = null): array
    {
        if (! config('geo.elevation_enabled') || empty($points)) {
            return $points;
        }

        if (! $this->needsEnrichment($points)) {
            return $points;
        }

        $chunks = array_chunk($points, config('geo.elevation_max_points_per_req', 100), true);
        $enriched = $points;
        $totalChunks = count($chunks);
        $lastIndex = $totalChunks - 1;

        foreach ($chunks as $chunkIndex => $chunk) {
            $elevations = $this->fetchElevations($chunk);

            foreach ($elevations as $originalIndex => $elevation) {
                $enriched[$originalIndex]['ele'] = $elevation;
            }

            if ($onProgress !== null) {
                $percent = (int) round(($chunkIndex + 1) / $totalChunks * 100);
                $onProgress($percent);
            }

            if ($chunkIndex < $lastIndex) {
                sleep(config('geo.elevation_rate_delay_s', 1));
            }
        }

        return $enriched;
    }

    /**
     * Vérifie si les points ont besoin d'enrichissement (tous les ele sont null).
     *
     * @param  array<int, array{ele: float|null}>  $points
     */
    public function needsEnrichment(array $points): bool
    {
        foreach ($points as $point) {
            if ($point['ele'] !== null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Appelle l'API OpenTopoData pour un chunk de points.
     *
     * @param  array<int, array{lat: float, lon: float}>  $chunk
     * @return array<int, float|null> Clés = index originaux des points
     */
    private function fetchElevations(array $chunk): array
    {
        $locations = implode('|', array_map(
            fn ($p) => "{$p['lat']},{$p['lon']}",
            $chunk
        ));

        $results = [];

        try {
            $response = Http::timeout(10)
                ->get(config('geo.elevation_endpoint'), [
                    'locations' => $locations,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $apiResults = $data['results'] ?? [];
                $indices = array_keys($chunk);

                foreach ($apiResults as $i => $result) {
                    $elevation = $result['elevation'] ?? null;
                    $originalIndex = $indices[$i] ?? null;

                    if ($originalIndex !== null) {
                        $results[$originalIndex] = is_numeric($elevation)
                            ? (float) round($elevation, 1)
                            : null;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning("ElevationEnrichmentService : échec de l'appel API — {$e->getMessage()}");
        }

        return $results;
    }
}
