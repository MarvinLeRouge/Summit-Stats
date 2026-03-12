<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('segments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['montee', 'descente', 'plat']);
            $table->enum('slope_class', ['lt5', '5_15', '15_25', '25_35', 'gt35']);
            $table->integer('order');

            $table->float('distance_km');
            $table->integer('elevation_delta');
            $table->integer('duration_seconds');
            $table->float('avg_speed_kmh');
            $table->float('avg_slope_pct');
            $table->float('avg_ascent_speed_mh')->nullable();

            $table->integer('point_index_start');
            $table->integer('point_index_end');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('segments');
    }
};