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
    | Couches DXF autorisées pour la découpe
    |--------------------------------------------------------------------------
    |
    | Liste des couches DXF qui seront comptabilisées pour le calcul
    | des dimensions et du périmètre de découpe. Si vide, toutes les
    | couches sont acceptées.
    |
    */

    'dxf_cut_layers' => array_filter(explode(',', env('LASER_DXF_CUT_LAYERS', ''))),

];
