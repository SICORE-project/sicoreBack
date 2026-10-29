<?php

/*
| Permissions par défaut des profils de base, attribuées par `php artisan permissions:sync`
| (ajout seulement, jamais de retrait). Les profils créés dans l'interface ne sont pas concernés.
|
| Nouvelle permission : une ligne dans config/permissions.php avec son 'groupe' ; si ce groupe est
| listé ici pour un profil, il la reçoit. Cas particulier : 'roles' => ['admin'] sur la ligne.
|
| Clés : alias (autres slugs du profil), tout, groupes, permissions (slugs en plus), sauf (exclus).
*/

$referentiels = [
    'parametrage.corps.read', 'parametrage.categories.read', 'parametrage.diplomes.read',
    'parametrage.specialites.read', 'parametrage.syndicats.read',
    'parametrage.institutions_financieres.read', 'parametrage.lieux_service.read',
    'parametrage.centres_formation.read',
];

return [
    'super_admin' => ['tout' => true],

    'admin' => [
        'alias' => ['administrateur'],
        'groupes' => ['administration', 'parametrage', 'personnel', 'indemnites', 'paie'],
    ],

    // Enseignants, IEF et paie de son IA
    'gestionnaire_ia' => [
        'groupes' => ['paie'],
        'sauf' => ['paie.mes_bulletins.read'],
        'permissions' => array_merge($referentiels, [
            'parametrage.ia.read', 'parametrage.ief.read', 'parametrage.ief.manage',
            'enseignants.read', 'enseignants.create', 'enseignants.update', 'enseignants.validate',
            'enseignants.search', 'enseignants.export', 'enseignants.comptes_bancaires.manage',
        ]),
    ],

    // Convocations et pièces justificatives, sans paie
    'decpc' => [
        'permissions' => array_merge($referentiels, [
            'pieces.read', 'pieces.manage', 'pieces.validate', 'convocations.read', 'convocations.manage',
            'parametrage.ia.read', 'parametrage.ief.read',
            'enseignants.read', 'enseignants.search',
        ]),
    ],

    'enseignant' => [
        'groupes' => ['espace_enseignant'],
        'permissions' => ['paie.mes_bulletins.read'],
    ],
];
