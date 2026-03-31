<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

/**
 * Migrates all data from the legacy SQLite database to the current PostgreSQL database.
 *
 * Tables migrated: users, activities, segments, track_points.
 * Volatile tables (sessions, cache, jobs, personal_access_tokens) are intentionally skipped.
 *
 * Run once after the first PostgreSQL migration, then delete this command.
 */
class MigrateFromSqlite extends Command
{
    protected $signature = 'db:migrate-from-sqlite
                            {--path= : Path to the SQLite file (default: database/database.sqlite)}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Migrates data from the legacy SQLite database to PostgreSQL (run once)';

    private const CHUNK_SIZE = 500;

    public function handle(): int
    {
        $sqlitePath = $this->option('path') ?? database_path('database.sqlite');

        if (! file_exists($sqlitePath)) {
            $this->error("SQLite file not found: {$sqlitePath}");

            return self::FAILURE;
        }

        $this->info("Source : {$sqlitePath}");
        $this->info('Target : '.config('database.default').' ('.config('database.connections.'.config('database.default').'.database').')');
        $this->newLine();

        if (! $this->option('force') && ! $this->confirm('This will insert data into the current database. Continue?')) {
            $this->info('Aborted.');

            return self::SUCCESS;
        }

        $this->registerSqliteConnection($sqlitePath);

        $this->migrateUsers();
        $this->migrateActivities();
        $this->migrateSegments();
        $this->migrateTrackPoints();
        $this->resetSequences();

        $this->newLine();
        $this->info('Migration completed successfully.');
        $this->info('You can now delete this command: app/Console/Commands/MigrateFromSqlite.php');

        return self::SUCCESS;
    }

    private function registerSqliteConnection(string $path): void
    {
        Config::set('database.connections.sqlite_source', [
            'driver' => 'sqlite',
            'database' => $path,
            'foreign_key_constraints' => false,
        ]);
    }

    private function migrateUsers(): void
    {
        $rows = DB::connection('sqlite_source')->table('users')->get();
        $this->info("Migrating users ({$rows->count()})...");

        DB::table('users')->insertOrIgnore($rows->map(fn ($r) => (array) $r)->toArray());
    }

    private function migrateActivities(): void
    {
        $rows = DB::connection('sqlite_source')->table('activities')->get();
        $this->info("Migrating activities ({$rows->count()})...");

        DB::table('activities')->insertOrIgnore($rows->map(fn ($r) => (array) $r)->toArray());
    }

    private function migrateSegments(): void
    {
        $rows = DB::connection('sqlite_source')->table('segments')->get();
        $this->info("Migrating segments ({$rows->count()})...");

        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();

        foreach ($rows->chunk(self::CHUNK_SIZE) as $chunk) {
            DB::table('segments')->insertOrIgnore($chunk->map(fn ($r) => (array) $r)->toArray());
            $bar->advance($chunk->count());
        }

        $bar->finish();
        $this->newLine();
    }

    private function migrateTrackPoints(): void
    {
        $total = DB::connection('sqlite_source')->table('track_points')->count();
        $this->info("Migrating track_points ({$total})...");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        DB::connection('sqlite_source')
            ->table('track_points')
            ->orderBy('id')
            ->chunk(self::CHUNK_SIZE, function ($chunk) use ($bar) {
                DB::table('track_points')->insertOrIgnore(
                    $chunk->map(fn ($r) => (array) $r)->toArray()
                );
                $bar->advance($chunk->count());
            });

        $bar->finish();
        $this->newLine();
    }

    /**
     * Resets PostgreSQL auto-increment sequences to the current max ID of each table.
     * Required after inserting rows with explicit IDs.
     */
    private function resetSequences(): void
    {
        $this->info('Resetting PostgreSQL sequences...');

        $tables = ['users', 'activities', 'segments', 'track_points'];

        foreach ($tables as $table) {
            $max = DB::table($table)->max('id') ?? 0;

            if ($max > 0) {
                DB::statement("SELECT setval('{$table}_id_seq', ?)", [$max]);
                $this->line("  {$table}: sequence reset to {$max}");
            }
        }
    }
}
