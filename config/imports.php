<?php

/*
|--------------------------------------------------------------------------
| Import des données de base (profil Admin System)
|--------------------------------------------------------------------------
| Une entrée par référentiel : table cible, colonne servant de clé (mise à jour si elle
| existe déjà, création sinon : un même fichier peut être réimporté sans doublon) et colonnes
| du fichier CSV. Types : texte, entier, decimal, date (AAAA-MM-JJ ou JJ/MM/AAAA), booleen (oui/non).
| 'cible' = colonne de la table (par défaut le nom de la colonne du fichier) ;
| 'reference' = code d'un autre référentiel, converti en identifiant.
*/

return [
    'ia' => [
        'libelle' => 'IA (Inspections d\'académie)',
        'table' => 'ias',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'IA-DK'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'IA de Dakar'],
            'adresse' => ['max' => 255],
            'telephone' => ['max' => 20],
            'email' => ['type' => 'email', 'max' => 100],
            'responsable' => ['max' => 100],
        ],
    ],
    'ief' => [
        'libelle' => 'IEF (Inspections de l\'éducation et de la formation)',
        'table' => 'iefs',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'IEF-DK1'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'IEF de Dakar-Plateau'],
            'ia_code' => ['requis' => true, 'cible' => 'ia_id', 'reference' => ['table' => 'ias', 'colonne' => 'code'], 'exemple' => 'IA-DK'],
            'adresse' => ['max' => 255],
            'telephone' => ['max' => 20],
            'email' => ['type' => 'email', 'max' => 100],
            'responsable' => ['max' => 100],
        ],
    ],
    'diplome' => [
        'libelle' => 'Diplômes',
        'table' => 'diplomes',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'BTS'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Brevet de technicien supérieur'],
            'niveau' => ['requis' => true, 'type' => 'entier', 'exemple' => '5'],
            'type' => ['max' => 50],
        ],
    ],
    'categorie' => [
        'libelle' => 'Catégories',
        'table' => 'categories',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 10, 'exemple' => 'A1'],
            'libelle' => ['requis' => true, 'max' => 50, 'exemple' => 'Catégorie A1'],
            'ordre' => ['requis' => true, 'type' => 'entier', 'exemple' => '1'],
            'description' => ['max' => 255],
        ],
    ],
    'corps' => [
        'libelle' => 'Corps',
        'table' => 'corps_enseignant',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'PROF'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Professeur'],
            'categorie_code' => ['requis' => true, 'cible' => 'categorie_id', 'reference' => ['table' => 'categories', 'colonne' => 'code'], 'exemple' => 'A1'],
            'description' => ['max' => 255],
        ],
    ],
    'banque' => [
        'libelle' => 'Banques (institutions financières)',
        'table' => 'institution_financieres',
        'cle' => 'nom',
        'colonnes' => [
            'nom' => ['requis' => true, 'max' => 191, 'exemple' => 'Banque exemple'],
            'adresse' => ['max' => 191],
            'telephone' => ['max' => 191],
        ],
    ],
    'specialite' => [
        'libelle' => 'Spécialités',
        'table' => 'specialites',
        'cle' => 'code',
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'MATH'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Mathématiques'],
            'domaine' => ['max' => 50],
            'description' => ['max' => 2000],
        ],
    ],
    'syndicat' => [
        'libelle' => 'Syndicats',
        'table' => 'syndicats',
        'cle' => 'code',
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'SYN1'],
            'nom' => ['requis' => true, 'max' => 100, 'exemple' => 'Syndicat exemple'],
            'sigle' => ['max' => 20],
            'taux_cotisation' => ['requis' => true, 'type' => 'decimal', 'exemple' => '1.5'],
            'adresse' => ['max' => 255],
            'telephone' => ['max' => 20],
            'email' => ['type' => 'email', 'max' => 100],
            'site_web' => ['max' => 100],
            'responsable' => ['max' => 100],
            'responsable_titre' => ['max' => 50],
        ],
    ],
    'annee_academique' => [
        'libelle' => 'Années académiques',
        'table' => 'annee_academiques',
        'cle' => 'libelle',
        'defauts' => ['en cours' => 0, 'est_cloturee' => 0],
        'colonnes' => [
            'libelle' => ['requis' => true, 'max' => 20, 'exemple' => '2026-2027'],
            'date_debut' => ['requis' => true, 'type' => 'date', 'exemple' => '2026-10-01'],
            'date_fin' => ['requis' => true, 'type' => 'date', 'exemple' => '2027-07-31'],
            'observations' => ['max' => 2000],
        ],
    ],
    'periode_paie' => [
        'libelle' => 'Périodes de paie',
        'table' => 'periode_de_paies',
        'cle' => 'code',
        'defauts' => ['est_fermee' => 0, 'est_verrouillee' => 0],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 191, 'exemple' => '2026-10'],
            'libelle' => ['requis' => true, 'max' => 50, 'exemple' => 'Octobre 2026'],
            'mois' => ['requis' => true, 'type' => 'entier', 'min' => 1, 'maxvaleur' => 12, 'exemple' => '10'],
            'annee' => ['requis' => true, 'type' => 'entier', 'exemple' => '2026'],
            'date_debut' => ['requis' => true, 'type' => 'date', 'exemple' => '2026-10-01'],
            'date_fin' => ['requis' => true, 'type' => 'date', 'exemple' => '2026-10-31'],
            'annee_academique' => ['requis' => true, 'cible' => 'annee_academique_id', 'reference' => ['table' => 'annee_academiques', 'colonne' => 'libelle'], 'exemple' => '2026-2027'],
            'date_paiement' => ['type' => 'date'],
            'date_limite_saisie' => ['type' => 'date'],
            'date_limite_validation' => ['type' => 'date'],
        ],
    ],
    'rubrique_paie' => [
        'libelle' => 'Rubriques de paie',
        'table' => 'rubrique_paies',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'IND_RES'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Indemnité de responsabilité'],
            'type' => ['requis' => true, 'valeurs' => ['gain', 'retenue'], 'exemple' => 'gain'],
        ],
    ],
    'etablissement' => [
        'libelle' => 'Établissements (lieux de service)',
        'table' => 'lieu_de_services',
        'cle' => 'code',
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'ETAB-001'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Lycée exemple'],
            'ia_code' => ['cible' => 'ia_id', 'reference' => ['table' => 'ias', 'colonne' => 'code']],
            'ief_code' => ['cible' => 'ief_id', 'reference' => ['table' => 'iefs', 'colonne' => 'code']],
        ],
    ],
];
