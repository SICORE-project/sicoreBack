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

    // Administrateur = agent de la DAGE : tout le métier, sans la partie technique ni les suppressions.
    'admin' => [
        'alias' => ['administrateur'],
        'groupes' => ['administration', 'parametrage', 'personnel', 'indemnites', 'paie'],
        'sauf' => [
            // Technique : réservé au super administrateur (cellule informatique).
            'administration.roles.manage',
            'administration.permissions.manage',
            'parametrage.lieux_service.manage',
            // Suppressions : réservées au super administrateur.
            'administration.users.delete',
            'enseignants.delete',
            'parametrage.ia.delete',
            'parametrage.ief.delete',
        ],
    ],

    // Enseignants, IEF et paie de son IA
    'gestionnaire_ia' => [
        'groupes' => ['paie'],
        'sauf' => [
            'paie.mes_bulletins.read',
            // Délégations de crédits : réservées à l'administration centrale.
            'paie.delegation-credits.read',
            'paie.delegation-credits.update',
            'paie.edition-delegation.read',
            'paie.edition-engagement.read',
            'paie.projection.read',
        ],
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
