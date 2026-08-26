<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AccountController extends Controller
{
    public function __construct(private readonly AccountService $accounts)
    {
    }

    /** GET /api/v1/account/export — toutes ses donnees en JSON. */
    public function export(Request $request): JsonResponse
    {
        return response()->json($this->accounts->export($request->user()));
    }

    /** GET /api/v1/account/devices — les appareils connectes. */
    public function devices(Request $request): JsonResponse
    {
        $courant = $this->jetonCourant($request);

        return response()->json([
            'devices' => $request->user()->tokens()
                ->orderByDesc('last_used_at')
                ->get(['id', 'name', 'last_used_at', 'created_at'])
                ->map(fn ($t) => [
                    'id' => $t->id,
                    'name' => $t->name,
                    'last_used_at' => $t->last_used_at?->toIso8601String(),
                    'created_at' => $t->created_at?->toIso8601String(),
                    'is_current' => $courant !== null && $t->getKey() === $courant,
                ]),
        ]);
    }

    /** DELETE /api/v1/account/devices — deconnecte tous les AUTRES appareils. */
    public function revokeOtherDevices(Request $request): JsonResponse
    {
        $courant = $this->jetonCourant($request);

        $requete = $request->user()->tokens();

        if ($courant !== null) {
            $requete->where('id', '!=', $courant);
        }

        $nombre = $requete->delete();

        return response()->json([
            'message' => "{$nombre} appareil(s) deconnecte(s).",
        ]);
    }

    /**
     * L'identifiant du jeton d'API qui a servi a faire CETTE requete, ou null.
     *
     * `currentAccessToken()` rend soit un PersonalAccessToken (une ligne en
     * base, avec un id), soit un TransientToken quand l'utilisateur est
     * authentifie autrement : session web, ou `actingAs()` dans un test. Le
     * TransientToken n'a pas d'id — c'est tout son sens : « authentifie, mais
     * pas par un jeton d'API ». Ecrire ->id dessus levait une erreur PHP, que
     * le gestionnaire d'exceptions transformait en 500.
     *
     * On distingue donc les deux cas au lieu de supposer qu'il n'y en a qu'un.
     */
    private function jetonCourant(Request $request): int|string|null
    {
        $jeton = $request->user()->currentAccessToken();

        return $jeton instanceof PersonalAccessToken ? $jeton->getKey() : null;
    }

    /**
     * DELETE /api/v1/account
     *
     * Suppression definitive. On exige le mot de passe : c'est irreversible,
     * et un telephone laisse ouvert sur une table ne doit pas suffire.
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
            // Formulation a recopier : evite la suppression par megarde.
            'confirmation' => ['required', 'in:SUPPRIMER MON COMPTE'],
        ], [
            'confirmation.in' => 'Ecris exactement : SUPPRIMER MON COMPTE',
        ]);

        $user = $request->user();

        if (! Hash::check($request->string('password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Mot de passe incorrect.'],
            ]);
        }

        if (! $this->accounts->canDelete($user)) {
            return response()->json([
                'message' => 'Un compte de l\'equipe pastorale ne peut pas etre supprime '
                    . 'depuis l\'application : ses reponses sont lues par d\'autres membres. '
                    . 'Contacte un administrateur.',
            ], 403);
        }

        $this->accounts->delete($user);

        return response()->json([
            'message' => 'Ton compte et toutes tes donnees ont ete supprimes. Que Dieu te garde.',
        ]);
    }
}
