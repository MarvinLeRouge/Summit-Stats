<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Activity extends Model
{
    use HasFactory;

    protected $fillable = [
        'title', 'type', 'environment', 'date', 'comment', 'gpx_path',
        // Stats globales
        'distance_km', 'elevation_gain', 'elevation_loss',
        'duration_seconds', 'moving_duration_seconds',
        'avg_speed_kmh', 'avg_speed_moving_kmh',
        'avg_ascent_speed_mh', 'summit_ascent_speed_mh', 'longest_ascent_speed_mh', 'longest_ascent_distance_km',
        'avg_flat_speed_kmh', 'avg_descent_speed_kmh', 'avg_descent_rate_mh',
        // Répartition globale
        'pct_ascent', 'pct_flat', 'pct_descent',
        // Répartition montée par classe de pente
        'pct_ascent_lt5', 'pct_ascent_5_15', 'pct_ascent_15_25', 'pct_ascent_25_35', 'pct_ascent_gt35',
        // Répartition descente par classe de pente
        'pct_descent_lt5', 'pct_descent_5_15', 'pct_descent_15_25', 'pct_descent_25_35', 'pct_descent_gt35',
    ];

    protected $casts = [
        'date' => 'date',
        'distance_km' => 'float',
        'elevation_gain' => 'integer',
        'elevation_loss' => 'integer',
        'duration_seconds' => 'integer',
        'moving_duration_seconds' => 'integer',
        'avg_speed_kmh' => 'float',
        'avg_speed_moving_kmh' => 'float',
        'avg_ascent_speed_mh' => 'float',
        'summit_ascent_speed_mh' => 'float',
        'longest_ascent_speed_mh' => 'float',
        'longest_ascent_distance_km' => 'float',
        'avg_flat_speed_kmh' => 'float',
        'avg_descent_speed_kmh' => 'float',
        'avg_descent_rate_mh' => 'float',
        'pct_ascent' => 'float',
        'pct_flat' => 'float',
        'pct_descent' => 'float',
        'pct_ascent_lt5' => 'float',
        'pct_ascent_5_15' => 'float',
        'pct_ascent_15_25' => 'float',
        'pct_ascent_25_35' => 'float',
        'pct_ascent_gt35' => 'float',
        'pct_descent_lt5' => 'float',
        'pct_descent_5_15' => 'float',
        'pct_descent_15_25' => 'float',
        'pct_descent_25_35' => 'float',
        'pct_descent_gt35' => 'float',
    ];

    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class);
    }

    public function getFormattedDurationAttribute(): string
    {
        $h = intdiv($this->duration_seconds, 3600);
        $m = intdiv($this->duration_seconds % 3600, 60);

        return "{$h}h".str_pad($m, 2, '0', STR_PAD_LEFT);
    }
}
