<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

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
            'generate-payslips' => [
                'generation_scope' => ['required', Rule::in(['ia', 'teacher'])],
                'ia_id' => ['nullable', 'required_if:generation_scope,ia', 'integer', 'exists:ias,id'],
                'ief_id' => ['nullable', 'integer', Rule::exists('iefs', 'id')->where('ia_id', (int) $this->input('ia_id'))],
                'statut_enseignant_id' => ['nullable', 'integer', 'exists:statuts_enseignant,id'],
                'enseignant_id' => ['nullable', 'required_if:generation_scope,teacher', 'integer', $this->activeTeacherRule()],
                'annee_academique_id' => ['required', 'integer', 'exists:annee_academiques,id'],
                'month' => ['required', 'integer', 'between:1,12'],
            ],
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
                    $this->activeTeacherRule(),
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
                'impr_monthly_amount' => ['prohibited'],
                'trimf_monthly_amount' => ['prohibited'],
                'ipm_monthly_amount' => ['prohibited'],
                'union_checkoff_monthly_amount' => ['prohibited'],
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
                    $this->activeTeacherRule(),
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
                    $this->activeTeacherRule(),
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
            'prohibited' => 'Ce montant se configure dans Paramétrage → Rubriques de paie, pas dans le profil individuel.',
            'statut_enseignant_id.required' => 'Sélectionnez le statut enseignant concerné.',
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
            'statut_enseignant_id' => [
                'required',
                'integer',
                Rule::exists('statuts_enseignant', 'id')->where(
                    fn ($query) => $query->whereRaw("(LOWER(libelle) LIKE '%contractuel%' OR LOWER(libelle) LIKE '%vacataire%')")
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

    private function activeTeacherRule(): Exists
    {
        $column = Schema::hasColumn('enseignants', 'est_actif') ? 'est_actif' : 'actif';
        $rule = Rule::exists('enseignants', 'id')->where($column, true);

        return Schema::hasColumn('enseignants', 'deleted_at')
            ? $rule->whereNull('deleted_at')
            : $rule;
    }
}
