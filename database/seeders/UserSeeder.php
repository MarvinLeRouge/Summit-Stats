<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('APP_USER_PASSWORD');
        $minLength = (int) env('APP_USER_PASSWORD_MIN_LENGTH', 12);

        if (empty($password)) {
            throw new RuntimeException("APP_USER_PASSWORD n'est pas défini. Renseigne-le dans le fichier .env avant de lancer le seed.");
        }

        if (strlen($password) < $minLength) {
            throw new RuntimeException("APP_USER_PASSWORD doit contenir au moins {$minLength} caractères (actuellement : ".strlen($password).').');
        }

        $user = User::firstOrCreate(
            ['email' => env('APP_USER_EMAIL', 'admin@summitstats.local')],
            [
                'name' => env('APP_USER_NAME', 'Summit Stats User'),
                'password' => bcrypt($password),
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
