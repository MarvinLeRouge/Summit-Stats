<?php

namespace App\Services\Gpx;

use App\Exceptions\GpxParseException;

class GpxAnalysisOrchestrator
{
    public function __construct(
        private GpxParserService $parser,
        private SegmentationService $segmentation,
        private StatsAggregatorService $aggregator,
    ) {}

    /**
     * Analyse complète d'une trace GPX.
     * Pipeline : GpxParserService → SegmentationService → StatsAggregatorService.
     *
     * @param  string  $gpxFilePath  Chemin absolu vers le fichier GPX
     * @return array{activity_stats: array, segments: array}
     *
     * @throws GpxParseException Si le fichier est invalide ou introuvable
     */
    public function analyze(string $gpxFilePath): array
    {
        $points = $this->parser->parse($gpxFilePath);
        $segments = $this->segmentation->segment($points);

        return $this->aggregator->aggregate($segments, $points);
    }
}
