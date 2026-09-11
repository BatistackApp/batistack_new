<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Taux de TVA par défaut
    |--------------------------------------------------------------------------
    |
    | Le taux de TVA appliqué aux devis et factures du module Laser.
    |
    */

    'vat_rate' => env('LASER_VAT_RATE', 20),

    /*
    |--------------------------------------------------------------------------
    | Préfixe de référence des commandes
    |--------------------------------------------------------------------------
    |
    | Le préfixe utilisé pour générer les références de commandes laser.
    |
    */

    'order_prefix' => env('LASER_ORDER_PREFIX', 'LAC'),

    /*
    |--------------------------------------------------------------------------
    | Couches DXF autorisées pour la découpe
    |--------------------------------------------------------------------------
    |
    | Liste des couches DXF qui seront comptabilisées pour le calcul
    | des dimensions et du périmètre de découpe. Si vide, toutes les
    | couches sont acceptées.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Préfixe de référence des bons de livraison
    |--------------------------------------------------------------------------
    |
    | Le préfixe utilisé pour générer les références de bons de livraison laser.
    |
    */

    'delivery_note_prefix' => env('LASER_DELIVERY_NOTE_PREFIX', 'LBL'),

    'dxf_cut_layers' => array_filter(array_map('trim', explode(',', env('LASER_DXF_CUT_LAYERS', '')))),

];
