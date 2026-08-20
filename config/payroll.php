<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Paramètres de calcul
    |--------------------------------------------------------------------------
    |
    | Ces valeurs sont centralisées pour permettre leur validation par les
    | services financiers avant la mise en production. Elles ne remplacent
    | pas un paramétrage réglementaire validé par l'organisation.
    |
    */
    'daily_salary_divisor' => (int) env('PAYROLL_DAILY_DIVISOR', 30),
    'workday_minutes' => (int) env('PAYROLL_WORKDAY_MINUTES', 480),
    'employee_social_rate' => (float) env('PAYROLL_EMPLOYEE_SOCIAL_RATE', 0.056),
    'employer_social_rate' => (float) env('PAYROLL_EMPLOYER_SOCIAL_RATE', 0.164),
    'income_tax_rate' => (float) env('PAYROLL_INCOME_TAX_RATE', 0.03),
    'income_tax_allowance' => (float) env('PAYROLL_INCOME_TAX_ALLOWANCE', 75000),
    'currency' => 'XOF',
    'api_token_expiration' => (int) env('SANCTUM_TOKEN_EXPIRATION', 120),

    'read_roles' => [
        'Administrateur',
        'Gestionnaire de paie',
        'Auditeur',
    ],
    'write_roles' => [
        'Administrateur',
        'Gestionnaire de paie',
    ],
    'close_roles' => [
        'Administrateur',
    ],
];
