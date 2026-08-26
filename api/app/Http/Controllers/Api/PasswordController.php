<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class PasswordController extends Controller
{
    /**
     * POST /api/v1/password/forgot
     *
     * Envoie le lien de reinitialisation.
     *
     * Repond TOUJOURS la meme chose, que l'adresse existe ou non. Dire
     * "aucun compte avec cette adresse" permettrait de decouvrir qui est
     * membre de la communaute en essayant des adresses une par une.
     */
    public function forgot(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'Si un compte existe avec cette adresse, un lien vient d\'y etre envoye.',
        ]);
    }

    /**
     * POST /api/v1/password/reset
     *
     * Applique le nouveau mot de passe a partir du jeton recu par courriel.
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => $password])->save();

                // Toutes les sessions sont revoquees : si quelqu'un avait pris
                // le controle du compte, il est ejecte par la reinitialisation.
                $user->tokens()->delete();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => ['Ce lien est invalide ou a expire. Demande-en un nouveau.'],
            ]);
        }

        return response()->json([
            'message' => 'Mot de passe modifie. Tu peux te connecter.',
        ]);
    }

    /**
     * POST /api/v1/password/change
     *
     * Changement depuis le profil, en connaissant l'ancien mot de passe.
     */
    public function change(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::defaults()],
        ]);

        $user = $request->user();

        if (! Hash::check($request->string('current_password')->toString(), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Mot de passe actuel incorrect.'],
            ]);
        }

        $user->forceFill(['password' => $request->string('password')->toString()])->save();

        // On revoque les AUTRES appareils, pas celui d'ou vient la demande :
        // l'utilisateur ne doit pas etre deconnecte par sa propre action.
        $this->revoquerLesAutresJetons($user, $request);

        return response()->json([
            'message' => 'Mot de passe modifie. Tes autres appareils ont ete deconnectes.',
        ]);
    }

    /**
     * Supprime tous les jetons SAUF celui qui a servi a faire la demande.
     *
     * `currentAccessToken()` ne rend pas toujours un jeton enregistre en base.
     * Quand l'utilisateur est authentifie autrement — une session web, ou
     * `actingAs()` dans un test — Sanctum rend un TransientToken : un objet qui
     * represente « authentifie, mais pas par un jeton d'API ». Il n'a donc pas
     * d'identifiant, et lui demander ->id levait une erreur qui devenait un 500.
     *
     * Cas par cas :
     *   - jeton d'API reel  -> on epargne celui-la, on supprime les autres ;
     *   - TransientToken    -> il n'y a aucun jeton courant a epargner, donc
     *                          tous les jetons d'API sont bien « les autres ».
     */
    private function revoquerLesAutresJetons(User $user, Request $request): int
    {
        $jeton = $request->user()->currentAccessToken();

        $courant = $jeton instanceof PersonalAccessToken ? $jeton->getKey() : null;

        $requete = $user->tokens();

        if ($courant !== null) {
            $requete->where('id', '!=', $courant);
        }

        return $requete->delete();
    }
}
