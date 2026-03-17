<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackPoint extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'order',
        'lat',
        'lon',
        'ele',
        'time',
        'distance_from_start_km',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'ele' => 'float',
        'time' => 'datetime',
        'distance_from_start_km' => 'float',
        'order' => 'integer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
