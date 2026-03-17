<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Services\Gpx\GpxAnalysisOrchestrator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RecalculateStats extends Command
{
    protected $signature = 'stats:recalculate {--id= : Recalculer une seule activité}';

    protected $description = 'Recalcule les stats de toutes les activités depuis leur fichier GPX';

    /**
     * Exécute la commande de recalcul des statistiques.
     */
    public function handle(GpxAnalysisOrchestrator $orchestrator): int
    {
        $query = Activity::query();

        if ($id = $this->option('id')) {
            $query->where('id', $id);
        }

        $activities = $query->get();

        if ($activities->isEmpty()) {
            $this->warn('Aucune activité trouvée.');

            return self::SUCCESS;
        }

        $this->info("Recalcul de {$activities->count()} activité(s)...");
        $bar = $this->output->createProgressBar($activities->count());
        $bar->start();

        $errors = [];

        foreach ($activities as $activity) {
            try {
                $path = Storage::disk('local')->path($activity->gpx_path);
                $analysis = $orchestrator->analyze($path);

                $activity->segments()->delete();

                foreach ($analysis['segments'] as $segment) {
                    $activity->segments()->create($segment);
                }

                $activity->update($analysis['activity_stats']);
            } catch (\Throwable $e) {
                $errors[] = "Activité #{$activity->id} ({$activity->title}) : {$e->getMessage()}";
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        if (! empty($errors)) {
            $this->error('Erreurs rencontrées :');
            foreach ($errors as $error) {
                $this->warn("  • {$error}");
            }

            return self::FAILURE;
        }

        $this->info('Recalcul terminé avec succès.');

        return self::SUCCESS;
    }
}
