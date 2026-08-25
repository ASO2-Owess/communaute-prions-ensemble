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

    'timeout' => (int) env('ANTHROPIC_TIMEOUT', 60),

    /*
    | Nombre de generations qu'un membre peut declencher par jour.
    |
    | Sans cette limite, un seul compte epuise le budget IA du projet en une
    | nuit. Le cache mutualise fait que la plupart des demandes ne declenchent
    | aucun appel : le quota ne compte que les generations reelles.
    */
    'daily_quota_per_user' => (int) env('AI_DAILY_QUOTA', 10),

];
