<?php

/**
 * Reglages de l'accompagnement pastoral.
 *
 * Ces valeurs sont dans un fichier de configuration et non codees en dur :
 * elles varient selon la charge reelle et devront etre ajustees sans toucher
 * au code (ADR-007, risque n 1 du projet).
 */
return [

    /*
    | Delai de reponse annonce a l'utilisateur, en jours.
    |
    | Le prototype affichait "En attente de reponse" indefiniment. Annoncer un
    | delai honnete vaut mieux qu'une attente sans horizon : c'est la premiere
    | parade au goulot d'etranglement pastoral.
    */
    'response_delay_days' => (int) env('PASTORAL_RESPONSE_DELAY_DAYS', 7),

    /*
    | Nombre maximum de questions sans reponse par membre.
    |
    | Avec 2 000+ membres et un seul repondant, laisser une personne ouvrir
    | vingt questions d'affilee sature la file au detriment de tous les autres.
    */
    'max_open_questions_per_user' => (int) env('PASTORAL_MAX_OPEN_QUESTIONS', 3),

    /*
    | Themes de pre-tri. Permettent au pasteur de traiter d'abord ce qui
    | compte, et prepareront le regroupement des questions recurrentes.
    */
    'topics' => [
        'foi',
        'priere',
        'famille',
        'couple',
        'finances',
        'sante',
        'vocation',
        'doute',
        'deuil',
        'autre',
    ],

];
