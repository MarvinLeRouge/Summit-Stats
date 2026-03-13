<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => env('APP_USER_EMAIL', 'admin@summitstats.local')],
            [
                'name' => env('APP_USER_NAME', 'Summit Stats User'),
                'password' => bcrypt(env('APP_USER_PASSWORD', 'changeme')),
            ]
        );

        // Révoquer les tokens existants
        $user->tokens()->delete();

        // Générer un token long
        $token = $user->createToken('main-token')->plainTextToken;

        $this->command->info("Token généré : {$token}");
        $this->command->warn('Copie ce token dans ton .env frontend et dans Insomnia/Postman.');
    }
}
