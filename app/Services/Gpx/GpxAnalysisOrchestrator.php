<?php

namespace App\Services\Gpx;

use App\Exceptions\GpxParseException;

class GpxAnalysisOrchestrator
{
    public function __construct(
        private GpxParserService $parser,
        private ElevationCalculatorService $calculator,
        private ElevationEnrichmentService $enrichment,
        private SegmentationService $segmentation,
        private StatsAggregatorService $aggregator,
    ) {}

    /**
     * Analyse complète d'une trace GPX.
     * Pipeline : GpxParserService → ElevationEnrichmentService → ElevationCalculatorService → SegmentationService → StatsAggregatorService.
     *
     * @param  string  $gpxFilePath  Chemin absolu vers le fichier GPX
     * @param  callable|null  $onProgress  Callback de progression pour l'enrichissement altimétrique : fn(int $percent) => void
     * @return array{activity_stats: array, segments: array, points: array}
     *
     * @throws GpxParseException Si le fichier est invalide ou introuvable
     */
    public function analyze(string $gpxFilePath, ?callable $onProgress = null): array
    {
        $points = $this->parser->parse($gpxFilePath);
        $points = $this->enrichment->enrich($points, $onProgress);
        $distances = $this->calculator->distancesFromStart($points);

        foreach ($points as $i => &$point) {
            $point['distance_from_start_km'] = $distances[$i];
        }
        unset($point);

        $segments = $this->segmentation->segment($points);

        return [
            ...$this->aggregator->aggregate($segments, $points),
            'points' => $points,
        ];
    }

    public function parseOnly(string $gpxFilePath): array
    {
        return $this->parser->parse($gpxFilePath);
    }
}
