<?php

return [
    // Catégories explicites : un corps inconnu n'est pas supposé fonctionnaire.
    'corps_fonctionnaires' => ['fonctionnaire', 'Fonctionnaire', 'FONCTIONNAIRE'],
    'corps_non_fonctionnaires' => ['contractuel', 'Contractuel', 'CONTRACTUEL', 'PC', 'vacataire', 'Vacataire', 'VACATAIRE'],
    'required_dossier_fields' => ['matricule', 'nom', 'prenom', 'date_naissance', 'corps_id', 'ia_id', 'ief_id', 'lieu_service_id'],
];
