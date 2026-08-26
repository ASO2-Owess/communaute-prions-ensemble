<?php

/**
 * Origines autorisees a appeler l'API depuis un navigateur.
 *
 * Le defaut de Laravel autorise TOUTES les origines ('*'). Acceptable en
 * developpement, dangereux en ligne : n'importe quel site pourrait faire
 * appeler notre API par le navigateur de nos membres.
 *
 * Nos jetons sont de type Bearer (pas de cookie), ce qui limite deja la
 * portee d'un abus — mais restreindre les origines ne coute rien.
 */
return [

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // Liste explicite, lue depuis .env. En production : uniquement la PWA.
    'allowed_origins' => array_filter(
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))
    ),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // false : on n'utilise pas de cookies de session, seulement des jetons.
    'supports_credentials' => false,

];
