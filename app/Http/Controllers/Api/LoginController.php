<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    use ApiResponse;

    /**
     * Authenticates the single user by password and returns a Sanctum token.
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate(['password' => 'required|string|max:255']);

        $user = User::first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            Log::warning('Failed login attempt', ['ip' => $request->ip()]);

            return $this->error('Mot de passe incorrect.', 401);
        }

        $token = $user->createToken('web')->plainTextToken;

        return $this->success(['token' => $token]);
    }

    /**
     * Revokes the current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->noContent();
    }
}
