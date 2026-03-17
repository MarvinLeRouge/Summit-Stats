<?php

namespace App\Providers;

use App\Services\ActivityService;
use App\Services\Gpx\ElevationCalculatorService;
use App\Services\Gpx\ElevationEnrichmentService;
use App\Services\Gpx\GpxAnalysisOrchestrator;
use App\Services\Gpx\GpxParserService;
use App\Services\Gpx\SegmentationService;
use App\Services\Gpx\StatsAggregatorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ActivityService::class, function ($app) {
            return new ActivityService(
                $app->make(GpxAnalysisOrchestrator::class),
                $app->make(ElevationEnrichmentService::class),
            );
        });
        $this->app->bind(GpxAnalysisOrchestrator::class, function ($app) {
            return new GpxAnalysisOrchestrator(
                $app->make(GpxParserService::class),
                $app->make(ElevationCalculatorService::class),
                $app->make(ElevationEnrichmentService::class),
                $app->make(SegmentationService::class),
                $app->make(StatsAggregatorService::class),
            );
        });
    }

    public function boot(): void
    {
        //
    }
}
