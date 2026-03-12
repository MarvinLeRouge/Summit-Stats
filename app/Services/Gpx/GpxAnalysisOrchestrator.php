<?php

namespace App\Services\Gpx;

class GpxAnalysisOrchestrator
{
    public function __construct(
        private GpxParserService       $parser,
        private SegmentationService    $segmentation,
        private StatsAggregatorService $aggregator,
    ) {}

    /**
     * Exécute le pipeline complet d'analyse d'un fichier GPX.
     *
     * @param  string $gpxFilePath Chemin absolu vers le fichier GPX
     * @return array{activity_stats: array, segments: array}
     * @throws \App\Exceptions\GpxParseException
     */
    public function analyze(string $gpxFilePath): array
    {
        $points   = $this->parser->parse($gpxFilePath);
        $segments = $this->segmentation->segment($points);

        return $this->aggregator->aggregate($segments, $points);
    }
}