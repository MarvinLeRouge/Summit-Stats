<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rayon de la Terre
    |--------------------------------------------------------------------------
    | Rayon moyen de la Terre utilisé dans la formule de Haversine.
    | Exprimé en km et en mètres pour éviter les conversions dans les services.
    */
    'earth_radius_km' => 6371,
    'earth_radius_m' => 6371000,
    'pause_threshold_seconds' => 30,
    'elevation_enabled' => env('ELEVATION_ENABLED', false),
    'elevation_endpoint' => env('ELEVATION_PROVIDER_ENDPOINT', 'https://api.opentopodata.org/v1/mapzen'),
    'elevation_max_points_per_req' => env('ELEVATION_PROVIDER_MAX_POINTS_PER_REQ', 100),
    'elevation_rate_delay_s' => env('ELEVATION_PROVIDER_RATE_DELAY_S', 1),

];
