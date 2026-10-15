<?php

/*
|--------------------------------------------------------------------------
| Liste unique des permissions de l'application
|--------------------------------------------------------------------------
| Générée par « php artisan permissions:export » à partir de la base, ou éditée
| à la main. Pour donner ces permissions à un autre environnement :
|   php artisan permissions:sync
| Une permission n'a d'effet que si une route la réclame :
|   ->middleware('permission:mon.slug')
*/
return [
    // === ADMINISTRATION ===
    ['nom' => 'Gérer les permissions', 'slug' => 'administration.permissions.manage', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'manage'],
    ['nom' => 'Consulter les permissions', 'slug' => 'administration.permissions.read', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'read'],
    ['nom' => 'Gérer les rôles', 'slug' => 'administration.roles.manage', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'manage'],
    ['nom' => 'Consulter les rôles', 'slug' => 'administration.roles.read', 'groupe' => 'administration', 'module' => 'roles', 'action' => 'read'],
    ['nom' => 'Créer un utilisateur', 'slug' => 'administration.users.create', 'groupe' => 'administration', 'module' => 'users', 'action' => 'create'],
    ['nom' => 'Supprimer un utilisateur', 'slug' => 'administration.users.delete', 'groupe' => 'administration', 'module' => 'users', 'action' => 'delete'],
    ['nom' => 'Consulter les utilisateurs', 'slug' => 'administration.users.read', 'groupe' => 'administration', 'module' => 'users', 'action' => 'read'],
    ['nom' => 'Modifier un utilisateur', 'slug' => 'administration.users.update', 'groupe' => 'administration', 'module' => 'users', 'action' => 'update'],

    // === INDEMNITES ===
    ['nom' => 'Gérer les convocations', 'slug' => 'convocations.manage', 'groupe' => 'indemnites', 'module' => 'convocations', 'action' => 'manage'],
    ['nom' => 'Consulter les convocations', 'slug' => 'convocations.read', 'groupe' => 'indemnites', 'module' => 'convocations', 'action' => 'read'],
    ['nom' => 'Gérer les indemnités', 'slug' => 'indemnites.manage', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'manage'],
    ['nom' => 'Consulter les indemnités', 'slug' => 'indemnites.read', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'read'],
    ['nom' => 'Valider les indemnités', 'slug' => 'indemnites.validate', 'groupe' => 'indemnites', 'module' => 'indemnites', 'action' => 'validate'],
    ['nom' => 'Gérer les pièces justificatives', 'slug' => 'pieces.manage', 'groupe' => 'indemnites', 'module' => 'pieces', 'action' => 'manage'],
    ['nom' => 'Consulter les pièces justificatives', 'slug' => 'pieces.read', 'groupe' => 'indemnites', 'module' => 'pieces', 'action' => 'read'],
    ['nom' => 'Valider ou rejeter les pièces justificatives', 'slug' => 'pieces.validate', 'groupe' => 'indemnites', 'module' => 'pieces', 'action' => 'validate'],

    // === PAIE ===
    ['nom' => 'Générer les bulletins de paie', 'slug' => 'paie.bulletins.generate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'generate'],
    ['nom' => 'Consulter les bulletins de paie', 'slug' => 'paie.bulletins.read', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'read'],
    ['nom' => 'Valider les bulletins de paie', 'slug' => 'paie.bulletins.validate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'validate'],
    ['nom' => 'Consulter mes propres bulletins', 'slug' => 'paie.mes_bulletins.read', 'groupe' => 'paie', 'module' => 'mes_bulletins', 'action' => 'read'],
    ['nom' => 'Gérer les rubriques de paie', 'slug' => 'paie.rubriques.manage', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'manage'],
    ['nom' => 'Consulter les rubriques de paie', 'slug' => 'paie.rubriques.read', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'read'],

    // === PARAMETRAGE ===
    ['nom' => 'Gérer les années académiques', 'slug' => 'parametrage.annees_academiques.manage', 'groupe' => 'parametrage', 'module' => 'annees_academiques', 'action' => 'manage'],
    ['nom' => 'Consulter les années académiques', 'slug' => 'parametrage.annees_academiques.read', 'groupe' => 'parametrage', 'module' => 'annees_academiques', 'action' => 'read'],
    ['nom' => 'Gérer les catégories', 'slug' => 'parametrage.categories.manage', 'groupe' => 'parametrage', 'module' => 'categories', 'action' => 'manage'],
    ['nom' => 'Consulter les catégories', 'slug' => 'parametrage.categories.read', 'groupe' => 'parametrage', 'module' => 'categories', 'action' => 'read'],
    ['nom' => 'Gérer les centres de formation', 'slug' => 'parametrage.centres_formation.manage', 'groupe' => 'parametrage', 'module' => 'centres_formation', 'action' => 'manage'],
    ['nom' => 'Consulter les centres de formation', 'slug' => 'parametrage.centres_formation.read', 'groupe' => 'parametrage', 'module' => 'centres_formation', 'action' => 'read'],
    ['nom' => 'Gérer les corps', 'slug' => 'parametrage.corps.manage', 'groupe' => 'parametrage', 'module' => 'corps', 'action' => 'manage'],
    ['nom' => 'Consulter les corps', 'slug' => 'parametrage.corps.read', 'groupe' => 'parametrage', 'module' => 'corps', 'action' => 'read'],
    ['nom' => 'Gérer les diplômes', 'slug' => 'parametrage.diplomes.manage', 'groupe' => 'parametrage', 'module' => 'diplomes', 'action' => 'manage'],
    ['nom' => 'Consulter les diplômes', 'slug' => 'parametrage.diplomes.read', 'groupe' => 'parametrage', 'module' => 'diplomes', 'action' => 'read'],
    ['nom' => 'Gérer les disciplines', 'slug' => 'parametrage.disciplines.manage', 'groupe' => 'parametrage', 'module' => 'disciplines', 'action' => 'manage'],
    ['nom' => 'Consulter les disciplines', 'slug' => 'parametrage.disciplines.read', 'groupe' => 'parametrage', 'module' => 'disciplines', 'action' => 'read'],
    ['nom' => 'Supprimer les IA', 'slug' => 'parametrage.ia.delete', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'delete'],
    ['nom' => 'Gérer les IA', 'slug' => 'parametrage.ia.manage', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'manage'],
    ['nom' => 'Consulter les IA', 'slug' => 'parametrage.ia.read', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'read'],
    ['nom' => 'Supprimer les IEF', 'slug' => 'parametrage.ief.delete', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'delete'],
    ['nom' => 'Gérer les IEF', 'slug' => 'parametrage.ief.manage', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'manage'],
    ['nom' => 'Consulter les IEF', 'slug' => 'parametrage.ief.read', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'read'],
    ['nom' => 'Gérer les institutions financières', 'slug' => 'parametrage.institutions_financieres.manage', 'groupe' => 'parametrage', 'module' => 'institutions_financieres', 'action' => 'manage'],
    ['nom' => 'Consulter les institutions financières', 'slug' => 'parametrage.institutions_financieres.read', 'groupe' => 'parametrage', 'module' => 'institutions_financieres', 'action' => 'read'],
    ['nom' => 'Gérer les lieux de service', 'slug' => 'parametrage.lieux_service.manage', 'groupe' => 'parametrage', 'module' => 'lieux_service', 'action' => 'manage'],
    ['nom' => 'Consulter les lieux de service', 'slug' => 'parametrage.lieux_service.read', 'groupe' => 'parametrage', 'module' => 'lieux_service', 'action' => 'read'],
    ['nom' => 'Gérer les périodes de paie', 'slug' => 'parametrage.periodes_paie.manage', 'groupe' => 'parametrage', 'module' => 'periodes_paie', 'action' => 'manage'],
    ['nom' => 'Consulter les périodes de paie', 'slug' => 'parametrage.periodes_paie.read', 'groupe' => 'parametrage', 'module' => 'periodes_paie', 'action' => 'read'],
    ['nom' => 'Gérer les spécialités', 'slug' => 'parametrage.specialites.manage', 'groupe' => 'parametrage', 'module' => 'specialites', 'action' => 'manage'],
    ['nom' => 'Consulter les spécialités', 'slug' => 'parametrage.specialites.read', 'groupe' => 'parametrage', 'module' => 'specialites', 'action' => 'read'],
    ['nom' => 'Gérer les syndicats', 'slug' => 'parametrage.syndicats.manage', 'groupe' => 'parametrage', 'module' => 'syndicats', 'action' => 'manage'],
    ['nom' => 'Consulter les syndicats', 'slug' => 'parametrage.syndicats.read', 'groupe' => 'parametrage', 'module' => 'syndicats', 'action' => 'read'],

    // === PERSONNEL ===
    ['nom' => 'Gérer les comptes bancaires des enseignants', 'slug' => 'enseignants.comptes_bancaires.manage', 'groupe' => 'personnel', 'module' => 'comptes_bancaires', 'action' => 'manage'],
    ['nom' => 'Créer un enseignant', 'slug' => 'enseignants.create', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'create'],
    ['nom' => 'Supprimer un enseignant', 'slug' => 'enseignants.delete', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'delete'],
    ['nom' => 'Exporter les enseignants', 'slug' => 'enseignants.export', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'export'],
    ['nom' => 'Consulter les enseignants', 'slug' => 'enseignants.read', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'read'],
    ['nom' => 'Rechercher un enseignant', 'slug' => 'enseignants.search', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'search'],
    ['nom' => 'Modifier un enseignant', 'slug' => 'enseignants.update', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'update'],
    ['nom' => 'Valider un enseignant', 'slug' => 'enseignants.validate', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'validate'],
];
