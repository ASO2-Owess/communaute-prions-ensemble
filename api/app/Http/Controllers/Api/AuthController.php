<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    /** POST /api/v1/register */
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            // Le cast 'password' => 'hashed' du modele fait le hachage.
            // Le mot de passe en clair n'est jamais ecrit en base.
            'password' => $request->string('password')->toString(),
            'role' => User::ROLE_MEMBER,
        ]);

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
        ], 201);
    }

    /** POST /api/v1/login */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->string('email')->toString())->first();

        // Un seul message pour "email inconnu" et "mot de passe faux".
        // Distinguer les deux revelerait quelles adresses ont un compte.
        if (! $user || ! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Identifiants incorrects.'],
            ]);
        }

        return response()->json([
            'user' => new UserResource($user),
            'token' => $user->createToken($this->deviceName($request))->plainTextToken,
        ]);
    }

    /**
     * POST /api/v1/logout — revoque uniquement le jeton de cet appareil.
     *
     * Se deconnecter d'un telephone ne doit pas deconnecter la tablette : on
     * supprime le jeton courant, pas tous les jetons.
     *
     * `currentAccessToken()` ne rend un jeton supprimable que si la requete est
     * bien arrivee avec un en-tete Bearer. Authentifie autrement (session web,
     * `actingAs()` en test), Sanctum rend un TransientToken, qui n'a ni
     * identifiant ni methode delete() — il n'y a alors rien a revoquer.
     */
    public function logout(Request $request): JsonResponse
    {
        $jeton = $request->user()->currentAccessToken();

        if ($jeton instanceof PersonalAccessToken) {
            $jeton->delete();
        }

        return response()->json(['message' => 'Deconnecte.']);
    }

    /** GET /api/v1/me */
    public function me(Request $request): UserResource
    {
        return new UserResource($request->user());
    }

    private function deviceName(Request $request): string
    {
        return $request->string('device_name')->toString() ?: 'appareil-inconnu';
    }
}
