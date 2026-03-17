<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Segment extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'type',
        'slope_class',
        'order',
        'distance_km',
        'elevation_delta',
        'duration_seconds',
        'avg_speed_kmh',
        'avg_slope_pct',
        'avg_ascent_speed_mh',
        'point_index_start',
        'point_index_end',
    ];

    protected $casts = [
        'distance_km' => 'float',
        'elevation_delta' => 'integer',
        'duration_seconds' => 'integer',
        'avg_speed_kmh' => 'float',
        'avg_slope_pct' => 'float',
        'avg_ascent_speed_mh' => 'float',
        'point_index_start' => 'integer',
        'point_index_end' => 'integer',
        'order' => 'integer',
    ];

    /**
     * Retourne l'activité à laquelle appartient ce segment.
     *
     * @return BelongsTo<Activity, Segment>
     */
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    /**
     * Indique si ce segment est une montée.
     */
    public function isAscent(): bool
    {
        return $this->type === 'montee';
    }
}
