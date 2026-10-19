<?php

/*
|--------------------------------------------------------------------------
| Import des données de base (profil Admin System)
|--------------------------------------------------------------------------
| Les colonnes reprennent les règles des formulaires de création du Paramétrage (requêtes
| Store*Request et modèles de app/Models/Parametrage) : mêmes champs obligatoires, mêmes longueurs.
|
| Par référentiel :
|   table, cle      table cible et colonne servant de clé : une ligne dont la clé existe déjà met à jour
|                   l'enregistrement, sinon elle est créée (un même fichier peut être réimporté)
|   defauts         valeurs écrites à la création (ignorées si la colonne n'existe pas)
|   generer         colonnes générées à la création (code d'établissement)
|   uniques         colonnes qui ne doivent pas déjà exister sur un autre enregistrement
|   colonnes        colonnes du fichier CSV
|
| Par colonne : requis, max, type (entier, decimal, date, email, booleen), valeurs (liste fermée),
| regex + message, min / maxvaleur, exemple ; cible = colonne de la table si elle diffère du nom
| dans le fichier ; reference = enregistrement d'un autre référentiel retrouvé par une ou plusieurs
| colonnes ; apres = la date doit être postérieure à une autre colonne du fichier.
*/

return [
    'ia' => [
        'libelle' => 'IA (Inspections d\'académie)',
        'table' => 'ias',
        'cle' => 'code',
        'uniques' => [],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 50, 'exemple' => 'IA-DK'],
            'libelle' => ['requis' => true, 'max' => 200, 'exemple' => 'IA de Dakar'],
            'region' => ['requis' => true, 'cible' => 'region_id', 'reference' => ['table' => 'regions', 'colonne' => ['code', 'nom']], 'exemple' => 'DK'],
        ],
    ],
    'ief' => [
        'libelle' => 'IEF (Inspections de l\'éducation et de la formation)',
        'table' => 'iefs',
        'cle' => 'code',
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'regex' => '/^[A-Z0-9\-_]+$/', 'regex_message' => 'majuscules, chiffres, tirets uniquement', 'exemple' => 'IEF-DK1'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'IEF de Dakar-Plateau'],
            'ia' => ['requis' => true, 'cible' => 'ia_id', 'reference' => ['table' => 'ias', 'colonne' => 'code'], 'exemple' => 'IA-DK'],
            'adresse' => ['max' => 255],
            'telephone' => ['max' => 20, 'regex' => '/^(?:\+221|221)?(70|75|76|77|78)[0-9]{7}$/', 'regex_message' => 'numéro sénégalais (77 123 45 67)'],
            'email' => ['type' => 'email', 'max' => 100],
            'responsable' => ['max' => 100],
            'est_actif' => ['type' => 'booleen'],
        ],
    ],
    'corps' => [
        'libelle' => 'Corps',
        'table' => 'corps_enseignant',
        'cle' => 'code',
        'uniques' => ['libelle'],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 50, 'exemple' => 'PROF'],
            'libelle' => ['requis' => true, 'max' => 255, 'exemple' => 'Professeur'],
            'description' => ['max' => 2000],
        ],
    ],
    'categorie' => [
        'libelle' => 'Catégories',
        'table' => 'categories',
        'cle' => 'libelle',
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'libelle' => ['requis' => true, 'max' => 255, 'exemple' => 'Catégorie A1'],
            'corps' => ['requis' => true, 'cible' => 'corps_id', 'reference' => ['table' => 'corps_enseignant', 'colonne' => 'code'], 'exemple' => 'PROF'],
            'ordre' => ['type' => 'entier', 'min' => 0, 'exemple' => '1'],
            'description' => ['max' => 2000],
            'est_actif' => ['type' => 'booleen'],
        ],
    ],
    'diplome' => [
        'libelle' => 'Diplômes',
        'table' => 'diplomes',
        'cle' => 'libelle',
        'colonnes' => [
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Licence professionnelle'],
            'categorie' => ['requis' => true, 'cible' => 'categorie_id', 'reference' => ['table' => 'categories', 'colonne' => 'libelle'], 'exemple' => 'Catégorie A1'],
            'salaire_brut' => ['requis' => true, 'type' => 'decimal', 'min' => 0, 'exemple' => '250000'],
        ],
    ],
    'banque' => [
        'libelle' => 'Banques (instituts financiers)',
        'table' => 'instituts_financieres',
        'cle' => 'libelle',
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'libelle' => ['requis' => true, 'max' => 150, 'exemple' => 'Banque exemple'],
            'sigle' => ['max' => 30, 'exemple' => 'BEX'],
            'type_institution' => ['requis' => true, 'max' => 50, 'exemple' => 'Banque'],
            'adresse' => ['max' => 255],
            'telephone' => ['max' => 20],
            'email' => ['type' => 'email', 'max' => 100],
            'code_banque' => ['max' => 5],
            'code_guichet' => ['max' => 5],
            'iban_exemple' => ['max' => 34],
            'est_actif' => ['type' => 'booleen'],
        ],
    ],
    'specialite' => [
        'libelle' => 'Spécialités',
        'table' => 'specialites',
        'cle' => 'code',
        'uniques' => ['libelle'],
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'MATH'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Mathématiques'],
            'est_actif' => ['type' => 'booleen'],
        ],
    ],
    'syndicat' => [
        'libelle' => 'Syndicats',
        'table' => 'syndicats',
        'cle' => 'code',
        'uniques' => ['libelle'],
        'defauts' => ['est_actif' => 1],
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'exemple' => 'SYN1'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Syndicat exemple'],
            'montant_check_off' => ['type' => 'decimal', 'min' => 0, 'exemple' => '1500'],
            'montant_oeuvre_sociale' => ['type' => 'decimal', 'min' => 0, 'exemple' => '500'],
            'est_actif' => ['type' => 'booleen'],
        ],
    ],
    'annee_academique' => [
        'libelle' => 'Années académiques',
        'table' => 'annee_academiques',
        'cle' => 'libelle',
        'defauts' => ['est_active' => 0, 'en cours' => 0, 'est_cloturee' => 0],
        'colonnes' => [
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => '2026-2027'],
            'date_debut' => ['requis' => true, 'type' => 'date', 'exemple' => '2026-10-01'],
            'date_fin' => ['requis' => true, 'type' => 'date', 'apres' => 'date_debut', 'exemple' => '2027-07-31'],
            'observations' => ['max' => 1000],
        ],
    ],
    'periode_paie' => [
        'libelle' => 'Périodes de paie',
        'table' => 'periode_de_paies',
        'cle' => 'code',
        'colonnes' => [
            'code' => ['requis' => true, 'max' => 20, 'regex' => '/^[A-Z0-9]+(?:[-_][A-Z0-9]+)*$/', 'regex_message' => 'majuscules et chiffres, séparés par - ou _', 'exemple' => 'OCT2026'],
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Octobre 2026'],
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
            'periodicite' => ['requis' => true, 'valeurs' => ['mensuelle', 'ponctuelle', 'annuelle'], 'exemple' => 'mensuelle'],
            'description' => ['max' => 1000],
        ],
    ],
    'etablissement' => [
        'libelle' => 'Établissements (lieux de service)',
        'table' => 'lieu_de_services',
        'cle' => 'libelle',
        'generer' => ['code'],
        'defauts' => ['type' => 'IEF', 'perimetre' => 'regional', 'est_actif' => 1],
        'colonnes' => [
            'libelle' => ['requis' => true, 'max' => 100, 'exemple' => 'Lycée exemple'],
            'ia' => ['requis' => true, 'cible' => 'ia_id', 'reference' => ['table' => 'ias', 'colonne' => 'code'], 'exemple' => 'IA-DK'],
            'ief' => ['requis' => true, 'cible' => 'ief_id', 'reference' => ['table' => 'iefs', 'colonne' => 'code', 'meme' => 'ia_id'], 'exemple' => 'IEF-DK1'],
            'telephone' => ['max' => 20],
        ],
    ],
];
