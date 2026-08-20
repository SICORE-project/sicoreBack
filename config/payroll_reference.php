<?php

return [
    'effective_from' => '2026-01-01',
    'salary_grid_source' => 'Grille des salaires bruts des professeurs contractuels reçue le 08/08/2026',
    'bulletin_source' => 'Bulletin de solde PC de juin 2026 reçu le 08/08/2026',

    'diplomas' => [
        'CAP' => 'CAP',
        'BEP' => 'BEP',
        'BAC_BT' => 'BAC / BT',
        'BTS_DUEL_DUES' => 'BTS / DUEL / DUES',
        'LICENCE' => 'Licence',
        'MASTER_MAITRISE' => 'Master / Maîtrise',
    ],

    'contract_salary_grid' => [
        1 => [138567, 147667, 152773, 172328, 177021, 187966],
        2 => [142745, 152300, 157662, 178194, 183122, 194614],
        3 => [147132, 157165, 162795, 184354, 189528, 201595],
        4 => [151739, 162273, 168185, 190822, 196254, 208925],
        5 => [156577, 167629, 173845, 197612, 203315, 216620],
        6 => [161657, 173240, 179788, 204738, 210726, 224696],
        7 => [166990, 179113, 186027, 212214, 218502, 233169],
        8 => [172587, 185255, 192575, 220054, 226658, 242055],
        9 => [178459, 191673, 199445, 228272, 235209, 251370],
        10 => [184617, 198374, 206650, 236882, 244170, 261130],
        11 => [191072, 205365, 214203, 245898, 253556, 271351],
        12 => [197835, 212653, 222117, 255334, 263382, 282049],
    ],

    'vacataire_base_salary' => 150000,

    /*
     * Décomposition historique visible sur le bulletin PC reçu.
     * La grille contractuelle contient déjà ces augmentations dans son salaire
     * de base courant : le moteur les détaille sans les additionner deux fois.
     */
    'contract_salary_increases' => [
        ['code' => 'AUG_JAN_2002', 'label' => 'Augmentation janvier 2002', 'amount' => 5000],
        ['code' => 'AUG_JAN_2003', 'label' => 'Augmentation janvier 2003', 'amount' => 5000],
        ['code' => 'AUG_JAN_2004', 'label' => 'Augmentation janvier 2004', 'amount' => 5000],
        ['code' => 'AUG_JAN_2005', 'label' => 'Augmentation janvier 2005', 'amount' => 10000],
        ['code' => 'AUG_OCT_2005', 'label' => 'Augmentation octobre 2005', 'amount' => 10000],
        ['code' => 'AUG_JAN_2006', 'label' => 'Augmentation janvier 2006', 'amount' => 10000],
        ['code' => 'AUG_OCT_2018', 'label' => 'Augmentation octobre 2018', 'amount' => 5000],
        ['code' => 'AUG_JAN_2019', 'label' => 'Augmentation janvier 2019', 'amount' => 5000],
    ],

    // Le BEP est rangé dans le même palier IRD de 70 000 FCFA que les
    // autres diplômes sous la licence. La valeur reste versionnée en base.
    'ird_rates' => [
        'CAP' => 70000,
        'BEP' => 70000,
        'BAC_BT' => 70000,
        'BTS_DUEL_DUES' => 70000,
        'LICENCE' => 80000,
        'MASTER_MAITRISE' => 90000,
    ],

    'contract_parameters' => [
        'PRIME_SPECIALE' => 20000,
        'INDEMNITE_COMPENSATION' => 60000,
        'IPRES_EMPLOYEE_RATE' => 0.056,
        'IPRES_EMPLOYER_RATE' => 0.084,
        'IPRES_CEILING' => 256000,
    ],
];
