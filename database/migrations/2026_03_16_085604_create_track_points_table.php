<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('track_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('order');
            $table->float('lat', 10, 7);
            $table->float('lon', 10, 7);
            $table->float('ele')->nullable();
            $table->timestamp('time')->nullable();
            $table->float('distance_from_start_km');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('track_points');
    }
};
