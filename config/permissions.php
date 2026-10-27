<?php

/*


Pour donner ces permissions à un autre environnement :
|   php artisan permissions:sync
| Une permission n'a d'effet que si une route la réclame :
|   ->middleware('permission:mon.slug')
|
| Convention :
   slug    = <groupe>.<module>.<action>
  
*/

return [

    // ADMINISTRATION
    ['nom' => 'Consulter les permissions', 'slug' => 'administration.permissions.read',   'groupe' => 'administration', 'module' => 'permissions', 'action' => 'read'],
    ['nom' => 'Gérer les permissions',     'slug' => 'administration.permissions.manage', 'groupe' => 'administration', 'module' => 'permissions', 'action' => 'manage'],

    ['nom' => 'Consulter les rôles',       'slug' => 'administration.roles.read',         'groupe' => 'administration', 'module' => 'roles',       'action' => 'read'],
    ['nom' => 'Gérer les rôles',           'slug' => 'administration.roles.manage',       'groupe' => 'administration', 'module' => 'roles',       'action' => 'manage'],

    ['nom' => 'Consulter les utilisateurs', 'slug' => 'administration.users.read',        'groupe' => 'administration', 'module' => 'users',       'action' => 'read'],
    ['nom' => 'Créer un utilisateur',       'slug' => 'administration.users.create',      'groupe' => 'administration', 'module' => 'users',       'action' => 'create'],
    ['nom' => 'Modifier un utilisateur',    'slug' => 'administration.users.update',      'groupe' => 'administration', 'module' => 'users',       'action' => 'update'],
    ['nom' => 'Supprimer un utilisateur',   'slug' => 'administration.users.delete',      'groupe' => 'administration', 'module' => 'users',       'action' => 'delete'],

    // INDEMNITÉS
    ['nom' => 'Consulter les convocations', 'slug' => 'convocations.read',   'groupe' => 'indemnites', 'module' => 'convocations', 'action' => 'read'],
    ['nom' => 'Gérer les convocations',     'slug' => 'convocations.manage', 'groupe' => 'indemnites', 'module' => 'convocations', 'action' => 'manage'],

    ['nom' => 'Consulter les indemnités',   'slug' => 'indemnites.read',     'groupe' => 'indemnites', 'module' => 'indemnites',   'action' => 'read'],
    ['nom' => 'Valider les indemnités',     'slug' => 'indemnites.validate', 'groupe' => 'indemnites', 'module' => 'indemnites',   'action' => 'validate'],
    ['nom' => 'Gérer les indemnités',       'slug' => 'indemnites.manage',   'groupe' => 'indemnites', 'module' => 'indemnites',   'action' => 'manage'],

    ['nom' => 'Consulter les pièces justificatives',            'slug' => 'pieces.read',     'groupe' => 'indemnites', 'module' => 'pieces', 'action' => 'read'],
    ['nom' => 'Valider ou rejeter les pièces justificatives',   'slug' => 'pieces.validate', 'groupe' => 'indemnites', 'module' => 'pieces', 'action' => 'validate'],
    ['nom' => 'Gérer les pièces justificatives',                'slug' => 'pieces.manage',   'groupe' => 'indemnites', 'module' => 'pieces', 'action' => 'manage'],

    // SYSTÈME
    ['nom' => 'Importer les données de base', 'slug' => 'systeme.import', 'groupe' => 'systeme', 'module' => 'import', 'action' => 'import'],

    // PAIE

    // Bulletins
    ['nom' => 'Consulter les bulletins de paie', 'slug' => 'paie.bulletins.read',     'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'read'],
    ['nom' => 'Générer les bulletins de paie',   'slug' => 'paie.bulletins.generate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'generate'],
    ['nom' => 'Valider les bulletins de paie',   'slug' => 'paie.bulletins.validate', 'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'validate'],
    ['nom' => 'Exporter les bulletins de paie',  'slug' => 'paie.bulletins.export',   'groupe' => 'paie', 'module' => 'bulletins', 'action' => 'export'],

    //  Rubriques de paie (paramétrage) 
    ['nom' => 'Consulter les rubriques de paie', 'slug' => 'paie.rubriques.read',   'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'read'],
    ['nom' => 'Gérer les rubriques de paie',     'slug' => 'paie.rubriques.manage', 'groupe' => 'paie', 'module' => 'rubriques', 'action' => 'manage'],

    //1. États de présence 
    ['nom' => 'Consulter les états de présence', 'slug' => 'paie.etats_presence.read',   'groupe' => 'paie', 'module' => 'etats_presence', 'action' => 'read'],
    ['nom' => 'Modifier les états de présence',  'slug' => 'paie.etats_presence.update', 'groupe' => 'paie', 'module' => 'etats_presence', 'action' => 'update'],

    //  2. Avance Tabaski 
    ['nom' => 'Consulter les avances Tabaski', 'slug' => 'paie.avance_tabaski.read',   'groupe' => 'paie', 'module' => 'avance_tabaski', 'action' => 'read'],
    ['nom' => 'Modifier les avances Tabaski',  'slug' => 'paie.avance_tabaski.update', 'groupe' => 'paie', 'module' => 'avance_tabaski', 'action' => 'update'],

    //  3. Edition 

    ['nom' => 'Accéder au module Édition', 'slug' => 'paie.edition',     'groupe' => 'paie', 'module' => 'edition', 'action' => 'access'],

    ['nom' => 'Consulter les editions', 'slug' => 'paie.edition.read',   'groupe' => 'paie', 'module' => 'edition', 'action' => 'read'],
    ['nom' => 'Modifier les editions',  'slug' => 'paie.edition.update', 'groupe' => 'paie', 'module' => 'edition', 'action' => 'update'],


    // 3. Retenue Tabaski 
    ['nom' => 'Consulter les retenues Tabaski', 'slug' => 'paie.retenue_tabaski.read',   'groupe' => 'paie', 'module' => 'retenue_tabaski', 'action' => 'read'],
    ['nom' => 'Modifier les retenues Tabaski',  'slug' => 'paie.retenue_tabaski.update', 'groupe' => 'paie', 'module' => 'retenue_tabaski', 'action' => 'update'],

    //  4. Retenue sur rappel 
    ['nom' => 'Consulter les retenues sur rappel', 'slug' => 'paie.retenue_rappel.read',   'groupe' => 'paie', 'module' => 'retenue_rappel', 'action' => 'read'],
    ['nom' => 'Modifier les retenues sur rappel',  'slug' => 'paie.retenue_rappel.update', 'groupe' => 'paie', 'module' => 'retenue_rappel', 'action' => 'update'],

    //  5. Jours travaillés 
    ['nom' => 'Consulter les jours travaillés',       'slug' => 'paie.jours_travailles.read',   'groupe' => 'paie', 'module' => 'jours_travailles', 'action' => 'read'],
    ['nom' => 'Saisir le nombre de jours travaillés', 'slug' => 'paie.jours_travailles.update', 'groupe' => 'paie', 'module' => 'jours_travailles', 'action' => 'update'],

    //  6. Exemptions 
    ['nom' => 'Consulter les exemptions', 'slug' => 'paie.exemptions.read',   'groupe' => 'paie', 'module' => 'exemptions', 'action' => 'read'],
    ['nom' => 'Exempter un enseignant',   'slug' => 'paie.exemptions.update', 'groupe' => 'paie', 'module' => 'exemptions', 'action' => 'update'],

    //  7. Clôture de la paie 
    ['nom' => 'Consulter la clôture de paie', 'slug' => 'paie.cloture.read',   'groupe' => 'paie', 'module' => 'cloture', 'action' => 'read'],
    ['nom' => 'Clôturer la paie',             'slug' => 'paie.cloture.update', 'groupe' => 'paie', 'module' => 'cloture', 'action' => 'update'],

    //  8. Sommes perçues 
    ['nom' => 'Consulter les sommes perçues', 'slug' => 'paie.sommes_percues.read',   'groupe' => 'paie', 'module' => 'sommes_percues', 'action' => 'read'],
    ['nom' => 'Modifier les sommes perçues',  'slug' => 'paie.sommes_percues.update', 'groupe' => 'paie', 'module' => 'sommes_percues', 'action' => 'update'],

    //  9. Heures supplémentaires 
    ['nom' => 'Consulter les heures supplémentaires', 'slug' => 'paie.heures_sup.read',   'groupe' => 'paie', 'module' => 'heures_sup', 'action' => 'read'],
    ['nom' => 'Générer les heures supplémentaires',   'slug' => 'paie.heures_sup.update', 'groupe' => 'paie', 'module' => 'heures_sup', 'action' => 'update'],

    //  10. Génération individuelle de la paie 
    ['nom' => 'Consulter la génération individuelle', 'slug' => 'paie.generation_individuelle.read',   'groupe' => 'paie', 'module' => 'generation_individuelle', 'action' => 'read'],
    ['nom' => 'Générer la paie individuelle',          'slug' => 'paie.generation_individuelle.update', 'groupe' => 'paie', 'module' => 'generation_individuelle', 'action' => 'update'],

    //  11. Travaux périodiques 
    ['nom' => 'Consulter les travaux périodiques', 'slug' => 'paie.travaux_periodiques.read',   'groupe' => 'paie', 'module' => 'travaux_periodiques', 'action' => 'read'],
    ['nom' => 'Modifier les travaux périodiques',  'slug' => 'paie.travaux_periodiques.update', 'groupe' => 'paie', 'module' => 'travaux_periodiques', 'action' => 'update'],

    //  12. Récapitulatif par banque (lecture seule) 
    ['nom' => 'Récapitulatif par banque', 'slug' => 'paie.recap_banque.read', 'groupe' => 'paie', 'module' => 'recap_banque', 'action' => 'read'],

    //  13. Cotisations sociales 
    ['nom' => 'Consulter les cotisations sociales', 'slug' => 'paie.cotisations.read',   'groupe' => 'paie', 'module' => 'cotisations', 'action' => 'read'],
    ['nom' => 'Modifier les cotisations sociales',  'slug' => 'paie.cotisations.update', 'groupe' => 'paie', 'module' => 'cotisations', 'action' => 'update'],

    //  14. État des salaires 
    ['nom' => 'Consulter l\'état des salaires', 'slug' => 'paie.etat_salaires.read',   'groupe' => 'paie', 'module' => 'etat_salaires', 'action' => 'read'],
    ['nom' => 'Modifier l\'état des salaires',  'slug' => 'paie.etat_salaires.update', 'groupe' => 'paie', 'module' => 'etat_salaires', 'action' => 'update'],

    //  15. Paie générée par IEF (lecture seule) 
    ['nom' => 'Paie générée par IEF', 'slug' => 'paie.effectifs_ief.read', 'groupe' => 'paie', 'module' => 'effectifs_ief', 'action' => 'read'],

    //  16. Salaires par banque (lecture seule) 
    ['nom' => 'Salaires par banque', 'slug' => 'paie.salaires_banque.read', 'groupe' => 'paie', 'module' => 'salaires_banque', 'action' => 'read'],

    // 17. Bulletins des salaires (lecture seule)
    ['nom' => 'Bulletins des salaires', 'slug' => 'paie.bulletins_salaires.read', 'groupe' => 'paie', 'module' => 'bulletins_salaires', 'action' => 'read'],

    // 18. Effectifs par corps
    ['nom' => 'Consulter les effectifs par corps', 'slug' => 'paie.effectifs_corps.read',   'groupe' => 'paie', 'module' => 'effectifs_corps', 'action' => 'read'],
    ['nom' => 'Modifier les effectifs par corps',  'slug' => 'paie.effectifs_corps.update', 'groupe' => 'paie', 'module' => 'effectifs_corps', 'action' => 'update'],

    // 19. Délégation de crédit
    ['nom' => 'Consulter les délégations de crédit', 'slug' => 'paie.delegations.read',   'groupe' => 'paie', 'module' => 'delegations', 'action' => 'read'],
    ['nom' => 'Modifier les délégations de crédit',  'slug' => 'paie.delegations.update', 'groupe' => 'paie', 'module' => 'delegations', 'action' => 'update'],

    // 20. Édition des délégations
    ['nom' => 'Éditer les délégations', 'slug' => 'paie.delegations.edit', 'groupe' => 'paie', 'module' => 'delegations', 'action' => 'edit'],

    // 21. Édition des engagements 
    ['nom' => 'Éditer les engagements',  'slug' => 'paie.engagements.edit',   'groupe' => 'paie', 'module' => 'engagements', 'action' => 'edit'],
    ['nom' => 'Modifier les engagements', 'slug' => 'paie.engagements.update', 'groupe' => 'paie', 'module' => 'engagements', 'action' => 'update'],

    //  Espace enseignant (mes bulletins) 
    ['nom' => 'Consulter mes propres bulletins', 'slug' => 'paie.mes_bulletins.read', 'groupe' => 'paie', 'module' => 'mes_bulletins', 'action' => 'read'],

    // ESPACE ENSEIGNANT (mes pièces justificatives)
    ['nom' => 'Consulter mes pièces justificatives', 'slug' => 'mes_pieces.read',    'groupe' => 'espace_enseignant', 'module' => 'mes_pieces', 'action' => 'read'],
    ['nom' => 'Déposer mes pièces justificatives',   'slug' => 'mes_pieces.deposer', 'groupe' => 'espace_enseignant', 'module' => 'mes_pieces', 'action' => 'deposer'],

    // RECRUTEMENT
    ['nom' => 'Consulter le recrutement',     'slug' => 'recruitment.read',       'groupe' => 'recruitment', 'module' => 'recruitment', 'action' => 'read'],
    ['nom' => 'Servir le recrutement',        'slug' => 'recruitment.service',    'groupe' => 'recruitment', 'module' => 'recruitment', 'action' => 'service'],
    ['nom' => 'Importer le recrutement',      'slug' => 'recruitment.import',     'groupe' => 'recruitment', 'module' => 'recruitment', 'action' => 'import'],
    ['nom' => 'Transmettre le recrutement',   'slug' => 'recruitment.transmit',   'groupe' => 'recruitment', 'module' => 'recruitment', 'action' => 'transmit'],
    ['nom' => 'Transitionner le recrutement', 'slug' => 'recruitment.transition', 'groupe' => 'recruitment', 'module' => 'recruitment', 'action' => 'transition'],

    // PARAMÉTRAGE

    //  Découpage administratif 
    ['nom' => 'Consulter les régions',    'slug' => 'parametrage.regions.read',       'groupe' => 'parametrage', 'module' => 'regions',      'action' => 'read'],
    ['nom' => 'Gérer les régions',        'slug' => 'parametrage.regions.manage',     'groupe' => 'parametrage', 'module' => 'regions',      'action' => 'manage'],
    ['nom' => 'Consulter les départements', 'slug' => 'parametrage.departements.read',  'groupe' => 'parametrage', 'module' => 'departements', 'action' => 'read'],
    ['nom' => 'Gérer les départements',   'slug' => 'parametrage.departements.manage', 'groupe' => 'parametrage', 'module' => 'departements', 'action' => 'manage'],
    ['nom' => 'Consulter les communes',   'slug' => 'parametrage.communes.read',      'groupe' => 'parametrage', 'module' => 'communes',     'action' => 'read'],
    ['nom' => 'Gérer les communes',       'slug' => 'parametrage.communes.manage',    'groupe' => 'parametrage', 'module' => 'communes',     'action' => 'manage'],

    //  Années académiques 
    ['nom' => 'Consulter les années académiques', 'slug' => 'parametrage.annees_academiques.read',   'groupe' => 'parametrage', 'module' => 'annees_academiques', 'action' => 'read'],
    ['nom' => 'Gérer les années académiques',     'slug' => 'parametrage.annees_academiques.manage', 'groupe' => 'parametrage', 'module' => 'annees_academiques', 'action' => 'manage'],

    //  Catégories 
    ['nom' => 'Consulter les catégories', 'slug' => 'parametrage.categories.read',   'groupe' => 'parametrage', 'module' => 'categories', 'action' => 'read'],
    ['nom' => 'Gérer les catégories',     'slug' => 'parametrage.categories.manage', 'groupe' => 'parametrage', 'module' => 'categories', 'action' => 'manage'],

    //  Centres de formation ----
    ['nom' => 'Consulter les centres de formation', 'slug' => 'parametrage.centres_formation.read',   'groupe' => 'parametrage', 'module' => 'centres_formation', 'action' => 'read'],
    ['nom' => 'Gérer les centres de formation',     'slug' => 'parametrage.centres_formation.manage', 'groupe' => 'parametrage', 'module' => 'centres_formation', 'action' => 'manage'],

    //  Corps ----
    ['nom' => 'Consulter les corps', 'slug' => 'parametrage.corps.read',   'groupe' => 'parametrage', 'module' => 'corps', 'action' => 'read'],
    ['nom' => 'Gérer les corps',     'slug' => 'parametrage.corps.manage', 'groupe' => 'parametrage', 'module' => 'corps', 'action' => 'manage'],

    // ---- Diplômes ----
    ['nom' => 'Consulter les diplômes', 'slug' => 'parametrage.diplomes.read',   'groupe' => 'parametrage', 'module' => 'diplomes', 'action' => 'read'],
    ['nom' => 'Gérer les diplômes',     'slug' => 'parametrage.diplomes.manage', 'groupe' => 'parametrage', 'module' => 'diplomes', 'action' => 'manage'],

    // ---- Disciplines ----
    ['nom' => 'Consulter les disciplines', 'slug' => 'parametrage.disciplines.read',   'groupe' => 'parametrage', 'module' => 'disciplines', 'action' => 'read'],
    ['nom' => 'Gérer les disciplines',     'slug' => 'parametrage.disciplines.manage', 'groupe' => 'parametrage', 'module' => 'disciplines', 'action' => 'manage'],

    // ---- IA (Inspections Académiques) ----
    ['nom' => 'Consulter les IA', 'slug' => 'parametrage.ia.read',   'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'read'],
    ['nom' => 'Gérer les IA',     'slug' => 'parametrage.ia.manage', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'manage'],
    ['nom' => 'Supprimer les IA', 'slug' => 'parametrage.ia.delete', 'groupe' => 'parametrage', 'module' => 'ia', 'action' => 'delete'],

    // ---- IEF (Inspections de l'Enseignement Fondamental) ----
    ['nom' => 'Consulter les IEF', 'slug' => 'parametrage.ief.read',   'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'read'],
    ['nom' => 'Gérer les IEF',     'slug' => 'parametrage.ief.manage', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'manage'],
    ['nom' => 'Supprimer les IEF', 'slug' => 'parametrage.ief.delete', 'groupe' => 'parametrage', 'module' => 'ief', 'action' => 'delete'],

    // ---- Institutions financières ----
    ['nom' => 'Consulter les institutions financières', 'slug' => 'parametrage.institutions_financieres.read',   'groupe' => 'parametrage', 'module' => 'institutions_financieres', 'action' => 'read'],
    ['nom' => 'Gérer les institutions financières',     'slug' => 'parametrage.institutions_financieres.manage', 'groupe' => 'parametrage', 'module' => 'institutions_financieres', 'action' => 'manage'],

    // ---- Lieux de service ----
    ['nom' => 'Consulter les lieux de service', 'slug' => 'parametrage.lieux_service.read',   'groupe' => 'parametrage', 'module' => 'lieux_service', 'action' => 'read'],
    ['nom' => 'Gérer les lieux de service',     'slug' => 'parametrage.lieux_service.manage', 'groupe' => 'parametrage', 'module' => 'lieux_service', 'action' => 'manage'],

    // ---- Périodes de paie ----
    ['nom' => 'Consulter les périodes de paie', 'slug' => 'parametrage.periodes_paie.read',   'groupe' => 'parametrage', 'module' => 'periodes_paie', 'action' => 'read'],
    ['nom' => 'Gérer les périodes de paie',     'slug' => 'parametrage.periodes_paie.manage', 'groupe' => 'parametrage', 'module' => 'periodes_paie', 'action' => 'manage'],

    // ---- Spécialités ----
    ['nom' => 'Consulter les spécialités', 'slug' => 'parametrage.specialites.read',   'groupe' => 'parametrage', 'module' => 'specialites', 'action' => 'read'],
    ['nom' => 'Gérer les spécialités',     'slug' => 'parametrage.specialites.manage', 'groupe' => 'parametrage', 'module' => 'specialites', 'action' => 'manage'],

    // ---- Syndicats ----
    ['nom' => 'Consulter les syndicats', 'slug' => 'parametrage.syndicats.read',   'groupe' => 'parametrage', 'module' => 'syndicats', 'action' => 'read'],
    ['nom' => 'Gérer les syndicats',     'slug' => 'parametrage.syndicats.manage', 'groupe' => 'parametrage', 'module' => 'syndicats', 'action' => 'manage'],

    // PERSONNEL / ENSEIGNANTS

    // ---- Comptes bancaires ----
    ['nom' => 'Gérer les comptes bancaires des enseignants', 'slug' => 'enseignants.comptes_bancaires.manage', 'groupe' => 'personnel', 'module' => 'comptes_bancaires', 'action' => 'manage'],

    // ---- Enseignants ----
    ['nom' => 'Consulter les enseignants',   'slug' => 'enseignants.read',     'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'read'],
    ['nom' => 'Créer un enseignant',         'slug' => 'enseignants.create',   'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'create'],
    ['nom' => 'Modifier un enseignant',      'slug' => 'enseignants.update',   'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'update'],
    ['nom' => 'Supprimer un enseignant',     'slug' => 'enseignants.delete',   'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'delete'],
    ['nom' => 'Rechercher un enseignant',    'slug' => 'enseignants.search',   'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'search'],
    ['nom' => 'Valider un enseignant',       'slug' => 'enseignants.validate', 'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'validate'],
    ['nom' => 'Exporter les enseignants',    'slug' => 'enseignants.export',   'groupe' => 'personnel', 'module' => 'enseignants', 'action' => 'export'],

];