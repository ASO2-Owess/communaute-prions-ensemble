<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barre l'acces a l'espace pastoral aux membres ordinaires.
 *
 * Applique par nom de classe directement dans routes/api.php : aucun alias a
 * declarer, et on voit dans le fichier de routes exactement ce qui protege
 * quoi.
 */
class EnsurePastor
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isPastor()) {
            // 403 : l'utilisateur est bien identifie, mais n'a pas le droit.
            // A distinguer de 401, qui signifie "je ne sais pas qui tu es".
            return response()->json([
                'message' => 'Cet espace est reserve a l\'equipe pastorale.',
            ], 403);
        }

        return $next($request);
    }
}
