<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    | Disque utilise pour les photos de profil.
    |
    | En developpement : 'public' (storage/app/public).
    | En production : 'r2' — le disque local d'un hebergement gratuit est
    | EPHEMERE, les fichiers disparaissent a chaque redemarrage du service.
    */
    'avatars' => env('AVATAR_DISK', 'public'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
            'report' => false,
        ],

        /*
        | Cloudflare R2 — stockage objet compatible S3.
        |
        | 10 Go gratuits, sans expiration, et surtout SANS FRAIS DE SORTIE :
        | chaque affichage du classement charge 20 photos, et ailleurs ce
        | trafic sortant se facture au gigaoctet.
        |
        | Laravel parle S3 nativement : passer du disque local a R2 est un
        | changement de configuration, pas de code. C'est exactement le
        | benefice d'avoir isole le stockage dans AvatarService.
        */
        'r2' => [
            'driver' => 's3',
            'key' => env('R2_ACCESS_KEY_ID'),
            'secret' => env('R2_SECRET_ACCESS_KEY'),
            'region' => 'auto',
            'bucket' => env('R2_BUCKET'),
            'endpoint' => env('R2_ENDPOINT'),
            'url' => env('R2_URL'),
            'use_path_style_endpoint' => true,
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
