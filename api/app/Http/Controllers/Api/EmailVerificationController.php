<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /** POST /api/v1/email/verification-notification */
    public function send(Request $request): JsonResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'Ton adresse est deja confirmee.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Courriel de confirmation envoye.']);
    }

    /**
     * GET /api/v1/email/verify/{id}/{hash}
     *
     * Route SIGNEE : Laravel verifie la signature du lien avant d'entrer ici.
     * Elle est publique parce qu'on clique depuis sa boite mail, pas depuis
     * l'application — il n'y a donc pas de jeton d'authentification.
     */
    public function verify(Request $request, int $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        // Le hash porte sur l'adresse : si elle a change entre l'envoi et le
        // clic, le lien ne vaut plus rien.
        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return response()->json(['message' => 'Lien de confirmation invalide.'], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Adresse deja confirmee.']);
        }

        $user->markEmailAsVerified();

        return response()->json(['message' => 'Adresse confirmee. Merci !']);
    }
}
