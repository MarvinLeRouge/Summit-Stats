<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->float('avg_speed_kmh')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('segments', function (Blueprint $table) {
            $table->float('avg_speed_kmh')->nullable(false)->change();
        });
    }
};
