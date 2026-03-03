<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Provider SEO actif
    |--------------------------------------------------------------------------
    | Valeurs possibles : 'dataforseo', 'moz', 'custom'
    | 'custom' = mode dégradé sans API (données nulles)
    */
    'provider' => env('SEO_PROVIDER', 'custom'),

    /*
    |--------------------------------------------------------------------------
    | Moz API v2
    |--------------------------------------------------------------------------
    | Clés disponibles sur : https://moz.com/api
    */
    'moz_access_id'  => env('MOZ_ACCESS_ID', ''),
    'moz_secret_key' => env('MOZ_SECRET_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | DataforSEO API
    |--------------------------------------------------------------------------
    | Credentials disponibles sur : https://app.dataforseo.com/api-access
    | Stockés chiffrés en base (users.dataforseo_login_encrypted, etc.)
    | Ces valeurs env sont un fallback pour les déploiements CI/config-driven.
    */
    'dataforseo_login'    => env('DATAFORSEO_LOGIN', ''),
    'dataforseo_password' => env('DATAFORSEO_PASSWORD', ''),

    /*
    |--------------------------------------------------------------------------
    | Rate limiting entre appels API (en millisecondes)
    |--------------------------------------------------------------------------
    | Évite de dépasser les quotas de l'API SEO lors des mises à jour batch.
    */
    'rate_limit_ms' => env('SEO_RATE_LIMIT_MS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Durée de validité des métriques en heures
    |--------------------------------------------------------------------------
    | Les métriques plus vieilles que cette durée sont considérées périmées.
    */
    'cache_hours' => env('SEO_CACHE_HOURS', 24),

];
