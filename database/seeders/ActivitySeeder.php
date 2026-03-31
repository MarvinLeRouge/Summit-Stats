<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;

class ActivitySeeder extends Seeder
{
    public function __construct(private readonly ActivityService $activityService) {}

    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->error('No user found. Run UserSeeder first.');

            return;
        }

        if ($user->activities()->exists()) {
            $this->command->info('Activities already seeded, skipping.');

            return;
        }

        $fixturePath = base_path('tests/Fixtures/gpx/real_track.gpx');

        $gpxFile = new UploadedFile(
            path: $fixturePath,
            originalName: 'real_track.gpx',
            mimeType: 'application/gpx+xml',
            error: UPLOAD_ERR_OK,
            test: true,
        );

        $activity = $this->activityService->store(
            metadata: [
                'title' => 'Sortie test E2E',
                'type' => 'trail',
                'environment' => 'montagne',
                'date' => now()->toDateString(),
                'comment' => null,
            ],
            gpxFile: $gpxFile,
        );

        $this->command->info("Activity seeded: [{$activity->id}] {$activity->title}");
    }
}
