<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dépôt des pièces justificatives
    |--------------------------------------------------------------------------
    | Les pièces justificatives sont fournies après les services faits, c'est-à-dire
    | après les examens : le dépôt est refusé tant que la date de fin de la convocation
    | n'est pas passée. Mettre INDEMNITES_DEPOT_APRES_EXAMEN=false pour lever ce
    | blocage (démonstration, essais).
    */
    'depot_apres_examen' => (bool) env('INDEMNITES_DEPOT_APRES_EXAMEN', true),
];
