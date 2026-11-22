<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dépôt des pièces justificatives
    |--------------------------------------------------------------------------
    | Par défaut, le dépôt des pièces justificatives est toujours possible, même
    | avant la fin de l'examen (demande utilisatrice : le bouton "Ajouter une
    | pièce" doit toujours être actif). Mettre INDEMNITES_DEPOT_APRES_EXAMEN=true
    | pour n'autoriser le dépôt qu'après la date de fin de la convocation.
    */
    'depot_apres_examen' => (bool) env('INDEMNITES_DEPOT_APRES_EXAMEN', false),
];
