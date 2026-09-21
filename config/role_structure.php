<?php

return [
    // Les règles par rôle sont prioritaires sur celles du type de rôle.
    'role_slugs' => [
        'agent_decpc' => ['DECPC'],
        'agent_drh' => ['DRH', 'IA', 'IEF', 'ETABLISSEMENT'],
        'gestionnaire_ia' => ['IA'],
        'super_admin' => ['DRH', 'DAGE', 'DECPC'],
        'admin' => ['DRH', 'DAGE', 'DECPC'],
        'drh' => ['DRH', 'DAGE', 'DECPC'],
        'gestionnaire_paie' => ['DRH', 'DAGE', 'DECPC'],
        'gestionnaire_budget' => ['DRH', 'DAGE', 'DECPC'],
        'consultant' => ['DRH', 'DAGE', 'DECPC'],
    ],

    'allowed_structure_types' => [
        'admin' => ['DRH', 'DAGE', 'DECPC'],
    ],
];
