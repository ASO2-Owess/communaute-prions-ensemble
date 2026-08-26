<?php

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',

        // Sans cette ligne, routes/api.php n'est jamais charge et toutes les
        // routes /api/* renvoient 404. `php artisan install:api` l'ajoute
        // normalement, mais il l'avait sautee car le fichier existait deja.
        api: __DIR__.'/../routes/api.php',

        /*
         | TOUTES les routes de l'API sont servies sous /api/v1/ (ADR-010).
         |
         | Une application mobile ne se met pas a jour toute seule. Une part
         | des membres tournera pendant des annees sur la version installee le
         | premier jour, et rien ne permet de les forcer a changer. Le jour ou
         | une reponse doit changer de forme, /api/v2/ sert les nouvelles
         | versions pendant que /api/v1/ continue de servir les anciennes.
         |
         | Ce mot coute une seconde aujourd'hui. Apres la publication sur les
         | magasins, l'ajouter reviendrait a casser toutes les installations
         | existantes : il n'y aurait plus de retour en arriere possible.
         |
         | Pour ouvrir la v2 le moment venu, on remplacera `api:` et
         | `apiPrefix:` par une closure `then:` qui enregistre deux fichiers
         | de routes, chacun avec son prefixe :
         |
         |     then: function () {
         |         Route::middleware('api')->prefix('api/v1')
         |              ->group(base_path('routes/api_v1.php'));
         |         Route::middleware('api')->prefix('api/v2')
         |              ->group(base_path('routes/api_v2.php'));
         |     },
         */
        apiPrefix: 'api/v1',

        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         |----------------------------------------------------------------------
         | Erreurs de l'API
         |----------------------------------------------------------------------
         |
         | LECON PAYEE COMPTANT (15 tests rouges).
         |
         | La version precedente attrapait TOUTES les exceptions de /api/* dans
         | une seule fonction, avec une suite de `if` : 401, 404, erreurs HTTP,
         | puis 500 pour le reste. Elle avait l'air complete. Elle ne l'etait
         | pas : ValidationException n'y figurait pas, donc chaque formulaire
         | mal rempli tombait dans la branche « erreur imprevue » et renvoyait
         | 500 au lieu de 422 — sans le detail des champs fautifs.
         |
         | Le probleme n'etait pas la branche oubliee. C'etait la forme :
         | un filet qui intercepte tout doit REFAIRE tout ce qu'il remplace.
         | Laravel savait deja rendre 422, 401, 403, 404 en JSON ; on l'avait
         | debranche pour rien.
         |
         | La bonne forme est l'inverse : on ne prend en charge que ce qu'on
         | veut vraiment changer, et on rend la main au framework pour le reste.
         */

        /*
         | Une seule chose manquait vraiment a Laravel ici : il ne repond en
         | JSON que si le client a envoye l'entete `Accept: application/json`.
         | Un client mobile qui l'oublie recoit une page HTML et affiche un
         | ecran blanc. Sur /api/*, la reponse est TOUJOURS du JSON.
         */
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request, \Throwable $e) => $request->is('api/*') || $request->expectsJson()
        );

        /*
         | Restent deux messages qu'on veut en francais, pour qu'ils soient
         | affichables tels quels par l'application. Chaque fonction rend
         | `null` hors de son cas : Laravel reprend alors la main.
         */
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json(['message' => 'Authentification requise.'], 401);
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json(['message' => 'Ressource introuvable.'], 404);
            }

            return null; // tout le reste : comportement natif de Laravel
        });
    })->create();
