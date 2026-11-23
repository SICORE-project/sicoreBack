
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Centres d'exécution
    |--------------------------------------------------------------------------
    |
    | Code budgétaire => IA correspondante dans SICORE.
    |
    | ia_code correspond au champ code de la table ias.
    | Les codes budgétaires restent distincts des codes IA.
    |
    */

    'centres_execution' => [

        'D01401343320100' => [
            'libelle' => 'IA DAKAR',
            'ia_code' => 'IA-DK',
        ],

        'D02421361510211' => [
            'libelle' => 'IA ZIGUINCHOR',
            'ia_code' => 'IA-ZG',
        ],

        'D03401343240300' => [
            'libelle' => 'IA DIOURBEL',
            'ia_code' => 'IA-DB',
        ],

        'D04401343270400' => [
            'libelle' => 'IA SAINT LOUIS',
            'ia_code' => 'IA-SL',
        ],

        'D05401342800500' => [
            'libelle' => 'IA TAMBACOUNDA',
            'ia_code' => 'IA-TC',
        ],

        'D06401343210600' => [
            'libelle' => 'IA KAOLACK',
            'ia_code' => 'IA-KL',
        ],

        'D07401343290700' => [
            'libelle' => 'IA THIES',
            'ia_code' => 'IA-TH',
        ],

        'D08401343250800' => [
            'libelle' => 'IA LOUGA',
            'ia_code' => 'IA-LG',
        ],

        'D09401343260900' => [
            'libelle' => 'IA FATICK',
            'ia_code' => 'IA-FK',
        ],

        'D10401343231000' => [
            'libelle' => 'IA KOLDA',
            'ia_code' => 'IA-KD',
        ],

        'D11421363460431' => [
            'libelle' => 'IA MATAM',
            'ia_code' => 'IA-MT',
        ],

        'D12013432100000' => [
            'libelle' => 'IA KAFFRINE',
            'ia_code' => 'IA-KA',
        ],

        'D13013432100000' => [
            'libelle' => 'IA KEDOUGOU',
            'ia_code' => 'IA-KE',
        ],

        'D14013432100000' => [
            'libelle' => 'IA SEDHIOU',
            'ia_code' => 'IA-SE',
        ],

    ],

    
    /*
    |--------------------------------------------------------------------------
    | Budgets
    |--------------------------------------------------------------------------
    */

    'budgets' => [

        'BF36237' => [
            'libelle' => 'VACATAIRES',
        ],

        'BF36238' => [
            'libelle' => 'PROFESSEURS CONTRACTUELS',
        ],

        'BF36239' => [
            'libelle' => 'AUTRES PRESTATIONS DE SERVICES',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Activités
    |--------------------------------------------------------------------------
    */

    'activites' => [

        '03P233010102' => [
            'libelle' => 'Salaire du personnel',
        ],

    ],


];
