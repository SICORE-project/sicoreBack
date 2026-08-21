<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PayrollActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return match ((string) $this->route('action')) {
            'configure-teacher-payroll' => [
                'ia_id' => ['required', 'integer', 'exists:ias,id'],
                'ief_id' => [
                    'required',
                    'integer',
                    Rule::exists('iefs', 'id')->where('ia_id', (int) $this->input('ia_id')),
                ],
                'matricule' => ['required', 'string', 'max:100'],
                'enseignant_id' => [
                    'required',
                    'integer',
                    Rule::exists('enseignants', 'id')->where('actif', true),
                ],
                'type_engagement' => ['required', Rule::in(['contractuel', 'vacataire'])],
                'payroll_diploma_level' => [
                    'nullable',
                    'required_if:type_engagement,contractuel',
                    Rule::in(['CAP', 'BEP', 'BAC_BT', 'BTS_DUEL_DUES', 'LICENCE', 'MASTER_MAITRISE']),
                ],
                'payroll_category_level' => [
                    'nullable',
                    'required_if:type_engagement,contractuel',
                    'integer',
                    'between:1,12',
                ],
                'impr_monthly_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'trimf_monthly_amount' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'ipm_monthly_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
                'union_checkoff_monthly_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
            ],
            'create-period' => [
                'code' => ['required', 'date_format:Y-m', 'unique:payroll_periods,code'],
                'label' => ['required', 'string', 'max:150'],
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            ],
            'save-attendance' => [
                'ia_id' => ['required', 'integer', 'exists:ias,id'],
                'ief_id' => [
                    'required',
                    'integer',
                    Rule::exists('iefs', 'id')->where('ia_id', (int) $this->input('ia_id')),
                ],
                'matricule' => ['required', 'string', 'max:100'],
                'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
                'enseignant_id' => [
                    'required',
                    'integer',
                    Rule::exists('enseignants', 'id')->where('actif', true),
                ],
                'absence_days' => ['required', 'numeric', 'min:0', 'max:31'],
                'delay_minutes' => ['required', 'integer', 'min:0', 'max:44640'],
                'deduction_amount' => ['nullable', 'numeric', 'min:0', 'max:999999999999.99'],
                'notes' => ['nullable', 'string', 'max:1000'],
                'expected_version' => ['nullable', 'integer', 'min:1'],
            ],
            'add-element' => [
                'ia_id' => ['required', 'integer', 'exists:ias,id'],
                'ief_id' => [
                    'required',
                    'integer',
                    Rule::exists('iefs', 'id')->where('ia_id', (int) $this->input('ia_id')),
                ],
                'matricule' => ['required', 'string', 'max:100'],
                'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
                'enseignant_id' => [
                    'required',
                    'integer',
                    Rule::exists('enseignants', 'id')->where('actif', true),
                ],
                'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/'],
                'label' => ['required', 'string', 'max:150'],
                'category' => ['required', Rule::in(['earning', 'deduction', 'contribution'])],
                'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
                'expected_version' => ['nullable', 'integer', 'min:1'],
            ],
            'apply-tabaski-advance' => $this->tabaskiRules(false),
            'apply-tabaski-deduction' => $this->tabaskiRules(true),
            'exempt-element' => [
                'payroll_element_id' => ['required', 'integer', 'exists:payroll_elements,id'],
                'reason' => ['required', 'string', 'min:10', 'max:1000'],
                'expected_version' => ['required', 'integer', 'min:1'],
            ],
            'validate-attendance',
            'validate-elements',
            'calculate-payroll',
            'validate-payroll' => [
                'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
            ],
            'close-period' => [
                'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
                'confirmation' => ['required', 'string', 'max:20'],
                'expected_version' => ['required', 'integer', 'min:1'],
            ],
            'mark-paid' => [
                'payroll_payslip_id' => ['required', 'integer', 'exists:payroll_payslips,id'],
                'payment_reference' => ['required', 'string', 'max:100'],
                'expected_version' => ['required', 'integer', 'min:1'],
            ],
            default => [],
        };
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'code.regex' => 'Le code ne peut contenir que des lettres majuscules, chiffres et underscores.',
            'reason.min' => 'Le motif doit être suffisamment précis (10 caractères minimum).',
            'expected_version.required' => 'La version de la donnée est requise pour éviter un écrasement concurrent.',
            'ia_id.required' => 'Sélectionnez d’abord une Inspection académique (IA).',
            'ief_id.required' => 'Sélectionnez ensuite une Inspection de l’Éducation et de la Formation (IEF).',
            'matricule.required' => 'Saisissez le matricule du formateur.',
            'enseignant_id.required' => 'Le matricule saisi n’a pas permis d’identifier un formateur.',
            'payroll_diploma_level.required_if' => 'Le diplôme de paie est obligatoire pour un professeur contractuel.',
            'payroll_category_level.required_if' => 'La catégorie de paie est obligatoire pour un professeur contractuel.',
            'impr_monthly_amount.required' => 'Le montant IMPR validé est obligatoire.',
            'trimf_monthly_amount.required' => 'Le montant TRIMF validé est obligatoire.',
            'corps_id.required' => 'Sélectionnez le corps d’enseignement concerné.',
            'ia_ids.required' => 'Sélectionnez au moins une Inspection académique (IA).',
            'ia_ids.min' => 'Sélectionnez au moins une Inspection académique (IA).',
            'annee_academique_id.required' => 'Sélectionnez l’année académique.',
            'month.required' => 'Sélectionnez le mois de l’avance Tabaski.',
            'months.required' => 'Sélectionnez les dix mois de retenue Tabaski.',
            'months.size' => 'La retenue Tabaski doit porter sur exactement 10 mois distincts.',
            'months.*.distinct' => 'Chaque mois de retenue doit être sélectionné une seule fois.',
            'amount.gt' => 'Le montant doit être strictement supérieur à zéro.',
        ];
    }

    /** @return array<string, mixed> */
    private function tabaskiRules(bool $deduction): array
    {
        $rules = [
            'corps_id' => [
                'required',
                'integer',
                Rule::exists('corps_enseignant', 'id')->where(
                    fn ($query) => $query->whereIn('code', ['VAC', 'PC'])
                ),
            ],
            'ia_ids' => ['required', 'array', 'min:1'],
            'ia_ids.*' => ['required', 'integer', 'distinct', 'exists:ias,id'],
            'annee_academique_id' => ['required', 'integer', 'exists:annee_academiques,id'],
            'amount' => ['required', 'numeric', 'gt:0', 'max:999999999999.99'],
        ];

        if ($deduction) {
            $rules['months'] = ['required', 'array', 'size:10'];
            $rules['months.*'] = ['required', 'integer', 'distinct', 'between:1,12'];
        } else {
            $rules['month'] = ['required', 'integer', 'between:1,12'];
        }

        return $rules;
    }
}
