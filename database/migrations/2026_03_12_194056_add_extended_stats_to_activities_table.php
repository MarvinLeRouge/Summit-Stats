<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            // Durée et vitesse en mouvement
            $table->integer('moving_duration_seconds')->nullable()->after('duration_seconds');
            $table->float('avg_speed_moving_kmh')->nullable()->after('avg_speed_kmh');

            // Vitesses ascensionnelles
            $table->float('summit_ascent_speed_mh')->nullable()->after('avg_ascent_speed_mh');
            $table->float('longest_ascent_speed_mh')->nullable()->after('summit_ascent_speed_mh');

            // Vitesses à plat et en descente
            $table->float('avg_flat_speed_kmh')->nullable()->after('longest_ascent_speed_mh');
            $table->float('avg_descent_speed_kmh')->nullable()->after('avg_flat_speed_kmh');
            $table->float('avg_descent_rate_mh')->nullable()->after('avg_descent_speed_kmh');

            // Répartition globale
            $table->float('pct_ascent')->nullable()->after('avg_descent_rate_mh');
            $table->float('pct_flat')->nullable()->after('pct_ascent');
            $table->float('pct_descent')->nullable()->after('pct_flat');

            // Répartition montée par classe de pente
            $table->float('pct_ascent_lt5')->nullable()->after('pct_descent');
            $table->float('pct_ascent_5_15')->nullable()->after('pct_ascent_lt5');
            $table->float('pct_ascent_15_25')->nullable()->after('pct_ascent_5_15');
            $table->float('pct_ascent_25_35')->nullable()->after('pct_ascent_15_25');
            $table->float('pct_ascent_gt35')->nullable()->after('pct_ascent_25_35');

            // Répartition descente par classe de pente
            $table->float('pct_descent_lt5')->nullable()->after('pct_ascent_gt35');
            $table->float('pct_descent_5_15')->nullable()->after('pct_descent_lt5');
            $table->float('pct_descent_15_25')->nullable()->after('pct_descent_5_15');
            $table->float('pct_descent_25_35')->nullable()->after('pct_descent_15_25');
            $table->float('pct_descent_gt35')->nullable()->after('pct_descent_25_35');
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn([
                'moving_duration_seconds', 'avg_speed_moving_kmh',
                'summit_ascent_speed_mh', 'longest_ascent_speed_mh',
                'avg_flat_speed_kmh', 'avg_descent_speed_kmh', 'avg_descent_rate_mh',
                'pct_ascent', 'pct_flat', 'pct_descent',
                'pct_ascent_lt5', 'pct_ascent_5_15', 'pct_ascent_15_25', 'pct_ascent_25_35', 'pct_ascent_gt35',
                'pct_descent_lt5', 'pct_descent_5_15', 'pct_descent_15_25', 'pct_descent_25_35', 'pct_descent_gt35',
            ]);
        });
    }
};
