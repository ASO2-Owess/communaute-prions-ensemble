<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Partage de ressources entre origines (CORS)
    |--------------------------------------------------------------------------
    |
    | Un navigateur refuse par defaut qu'une page servie par une origine
    | (http://localhost:53712, celle que Flutter web tire au hasard) appelle
    | une autre origine (http://127.0.0.1:8000, Laravel). C'est une protection
    | essentielle : sans elle, n'importe quel site pourrait faire des requetes
    | a ta banque avec tes cookies.
    |
    | Le serveur leve cette barriere pour les origines qu'il accepte, en
    | repondant a une requete preliminaire (OPTIONS) envoyee par le navigateur.
    |
    | Ce fichier n'existait pas : Laravel s'en passe tant qu'aucun client web
    | n'appelle l'API. Des qu'on lance `flutter run -d chrome`, il devient
    | indispensable.
    |
    | ATTENTION AVANT LA MISE EN PRODUCTION : remplacer le motif ci-dessous par
    | la liste EXACTE des origines autorisees. Ouvrir a tout le monde n'a de
    | sens qu'en developpement, ou les origines changent a chaque lancement.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    // En developpement, Flutter web choisit un port different a chaque
    // lancement : on autorise donc tout localhost, quel que soit le port.
    // `allowed_origins_patterns` accepte des expressions regulieres, la ou
    // `allowed_origins` veut des chaines exactes.
    'allowed_origins' => [],

    'allowed_origins_patterns' => [
        '#^https?://(localhost|127\.0\.0\.1)(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    // false : l'API s'authentifie par jeton Bearer, pas par cookie de session.
    // Passer a true autoriserait le navigateur a joindre les cookies aux
    // requetes entre origines — inutile ici, et une surface d'attaque de plus.
    'supports_credentials' => false,

];
