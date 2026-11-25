<?php

return [
    // Les règles par rôle sont prioritaires sur celles du type de rôle.
    'role_slugs' => [
        'enseignant' => ['IEF'],
        'agent_decpc' => ['DECPC'],
        'agent_drh' => ['DRH', 'IA', 'IEF', 'ETABLISSEMENT'],
        'gestionnaire_ia' => ['IA'],
        // Super administrateur : cellule informatique (partie technique).
        'super_admin' => ['CI'],
        // Administrateur : agent de la DAGE.
        'admin' => ['DAGE'],
        // DRH et DECPC sont des directions distinctes de la DAGE.
        'drh' => ['DRH'],
        'decpc' => ['DECPC'],
        'gestionnaire_paie' => ['DRH', 'DAGE', 'DECPC'],
        'gestionnaire_budget' => ['DRH', 'DAGE', 'DECPC'],
        'consultant' => ['DRH', 'DAGE', 'DECPC'],
    ],

    'allowed_structure_types' => [
        'admin' => ['DRH', 'DAGE', 'DECPC'],
    ],
];
