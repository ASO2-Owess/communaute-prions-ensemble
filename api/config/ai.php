<?php

/**
 * Relais vers l'IA (ADR-002).
 *
 * La cle vit ICI, cote serveur, et nulle part ailleurs. L'application mobile
 * ne la voit jamais : elle appelle notre API, qui appelle l'IA.
 */
return [

    /*
    | Cle d'API. Definie dans .env, jamais dans le code, jamais dans Git.
    */
    'key' => env('ANTHROPIC_API_KEY'),

    'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com'),

    /*
    | Modele utilise. Configurable : les noms de modeles evoluent, et on ne
    | veut pas modifier le code pour en changer.
    */
    'model' => env('ANTHROPIC_MODEL', 'claude-sonnet-4-6'),

    'max_tokens' => (int) env('ANTHROPIC_MAX_TOKENS', 2000),

    /*
    |--------------------------------------------------------------------------
    | Delai d'attente de l'appel a l'IA
    |--------------------------------------------------------------------------
    |
    | ABAISSE DE 60 A 25 SECONDES, et voici pourquoi.
    |
    | `php artisan serve` ne traite QU'UNE REQUETE A LA FOIS. Pendant qu'il
    | attend la reponse de l'IA, l'API entiere est gelee : la progression, les
    | lectures, le classement, tout attend derriere. Un membre qui ouvre une
    | etude bloquait donc l'application pendant une minute — pour lui ET pour
    | tous les autres.
    |
    | Vingt-cinq secondes suffisent largement a une generation normale, et
    | plafonnent les degats quand l'IA ne repond pas.
    |
    | LA VRAIE SOLUTION, pour plus tard : sortir la generation de la requete
    | HTTP en la confiant a une file d'attente (`QUEUE_CONNECTION=database` et
    | `php artisan queue:work`). Le serveur repond alors immediatement
    | « en cours de preparation », et rien ne bloque. C'est aussi ce qui
    | correspond le mieux au circuit reel : generation, puis relecture
    | pastorale, puis publication.
    |
    */
    'timeout' => (int) env('ANTHROPIC_TIMEOUT', 25),

    /*
    | Nombre de generations qu'un membre peut declencher par jour.
    |
    | Sans cette limite, un seul compte epuise le budget IA du projet en une
    | nuit. Le cache mutualise fait que la plupart des demandes ne declenchent
    | aucun appel : le quota ne compte que les generations reelles.
    */
    'daily_quota_per_user' => (int) env('AI_DAILY_QUOTA', 10),

];
