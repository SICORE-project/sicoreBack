<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVentilationDelegationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'corps_enseignant_id'   => 'nullable|exists:corps_enseignants,id',
            'ia_id'                 => 'nullable|exists:ias,id',
            'ief_id'                => 'nullable|exists:iefs,id',
            'centre_execution_id'   => 'nullable|exists:centres_execution,id',
            'budget_id'             => 'nullable|exists:budgets,id',
            'activite_id'           => 'nullable|exists:activites,id',
            'imputation_budgetaire' => 'nullable|string|max:50',
            'numero_autorisation'   => 'nullable|string|max:50',
            'numero_carton'         => 'nullable|string|max:50',
            'montant'               => 'required|numeric|min:0',
            'montant_engagement'    => 'nullable|numeric|min:0|lte:montant',
            'type'                  => 'required|in:salaire,prime_scolaire',
        ];
    }

    public function messages(): array
    {
        return [
            'montant_engagement.lte' => "L'engagement ne peut pas dépasser le montant de la ventilation.",
        ];
    }
}
