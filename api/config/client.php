<?php

/**
 * Adresse de l'application cliente (PWA aujourd'hui, Flutter par lien profond
 * demain).
 *
 * Sert a construire les liens envoyes par courriel : c'est le CLIENT qui
 * affiche le formulaire de nouveau mot de passe, pas l'API.
 *
 * On passe par un fichier de configuration et non par env() dans le code :
 * une fois la configuration mise en cache en production (`config:cache`),
 * les appels a env() renvoient null.
 */
return [

    'url' => env('FRONTEND_URL', env('APP_URL', 'http://localhost:8000')),

];
