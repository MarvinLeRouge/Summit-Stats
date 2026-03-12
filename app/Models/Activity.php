<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Activity extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'type',
        'environment',
        'date',
        'comment',
        'gpx_path',
        'distance_km',
        'elevation_gain',
        'elevation_loss',
        'duration_seconds',
        'avg_speed_kmh',
        'avg_ascent_speed_mh',
    ];

    protected $casts = [
        'date'                => 'date',
        'distance_km'         => 'float',
        'elevation_gain'      => 'integer',
        'elevation_loss'      => 'integer',
        'duration_seconds'    => 'integer',
        'avg_speed_kmh'       => 'float',
        'avg_ascent_speed_mh' => 'float',
    ];

    public function segments(): HasMany
    {
        return $this->hasMany(Segment::class)->orderBy('order');
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) return '00:00:00';

        $h = intdiv($this->duration_seconds, 3600);
        $m = intdiv($this->duration_seconds % 3600, 60);
        $s = $this->duration_seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }
}