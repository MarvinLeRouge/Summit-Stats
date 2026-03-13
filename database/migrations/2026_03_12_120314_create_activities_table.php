<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['randonnee', 'trail']);
            $table->enum('environment', ['urbain', 'campagne', 'montagne']);
            $table->date('date');
            $table->text('comment')->nullable();
            $table->string('gpx_path');

            // Stats globales
            $table->float('distance_km')->nullable();
            $table->integer('elevation_gain')->nullable();
            $table->integer('elevation_loss')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->float('avg_speed_kmh')->nullable();
            $table->float('avg_ascent_speed_mh')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
