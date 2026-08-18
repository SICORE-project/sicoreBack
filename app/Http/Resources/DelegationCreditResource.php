<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DelegationCreditResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'objet' => $this->objet,
            'annee_academique' => $this->annee_academique,
            'montant_initial' => (float) $this->montant_initial,
            'montant_disponible' => (float) $this->montant_disponible,
            'montant_engage' => (float) $this->montant_engage,
            'montant_consomme' => (float) $this->montant_consomme,
            'solde' => (float) $this->solde,
            'date_delegation' => $this->date_delegation?->toDateString(),
            'date_fin' => $this->date_fin?->toDateString(),
            'statut' => $this->statut,
            'structure_id' => $this->structure_id,
            'service_id' => $this->service_id,
            'structure' => $this->whenLoaded('structure', fn () => [
                'id' => $this->structure->id,
                'nom' => $this->structure->nom,
            ]),
            'service' => $this->whenLoaded('service', fn () => [
                'id' => $this->service->id,
                'nom' => $this->service->nom,
            ]),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
