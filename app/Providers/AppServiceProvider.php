<?php

namespace App\Providers;

use App\Services\Gpx\GpxAnalysisOrchestrator;
use App\Services\Gpx\GpxParserService;
use App\Services\Gpx\SegmentationService;
use App\Services\Gpx\StatsAggregatorService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(\App\Services\ActivityService::class, function ($app) {
            return new \App\Services\ActivityService(
                $app->make(\App\Services\Gpx\GpxAnalysisOrchestrator::class),
            );
        });
        $this->app->bind(GpxAnalysisOrchestrator::class, function ($app) {
            return new GpxAnalysisOrchestrator(
                $app->make(GpxParserService::class),
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