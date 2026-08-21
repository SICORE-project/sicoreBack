<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentilationDelegationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'delegation_credit_id' => $this->delegation_credit_id,

            'corps_enseignant_id' => $this->corps_enseignant_id,
            'ia_id' => $this->ia_id,
            'ief_id' => $this->ief_id,

            'centre_execution_id' => $this->centre_execution_id,
            'budget_id' => $this->budget_id,
            'activite_id' => $this->activite_id,
            'imputation_budgetaire' => $this->imputation_budgetaire,

            'numero_autorisation' => $this->numero_autorisation,
            'numero_carton' => $this->numero_carton,

            'montant' => (float) $this->montant,
            'montant_engagement' => (float) $this->montant_engagement,
            'disponible' => (float) $this->disponible,

            'type' => $this->type,

            'ia' => $this->whenLoaded('ia', fn () => [
                'id' => $this->ia->id,
                'code' => $this->ia->code,
                'libelle' => $this->ia->libelle,
            ]),
            'ief' => $this->whenLoaded('ief', fn () => [
                'id' => $this->ief->id,
                'code' => $this->ief->code,
                'libelle' => $this->ief->libelle,
            ]),
            'corps_enseignant' => $this->whenLoaded('corpsEnseignant', fn () => [
                'id' => $this->corpsEnseignant->id,
                'libelle' => $this->corpsEnseignant->libelle,
            ]),
            'centre_execution' => $this->whenLoaded('centreExecution', fn () => [
                'id' => $this->centreExecution->id,
                'code' => $this->centreExecution->code,
                'libelle' => $this->centreExecution->libelle,
            ]),
            'budget' => $this->whenLoaded('budget', fn () => [
                'id' => $this->budget->id,
                'code' => $this->budget->code,
                'libelle' => $this->budget->libelle,
            ]),
            'activite' => $this->whenLoaded('activite', fn () => [
                'id' => $this->activite->id,
                'code' => $this->activite->code,
                'libelle' => $this->activite->libelle,
            ]),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
