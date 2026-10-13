<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommuneResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,

            'region_id' => $this->region_id,

            'region' => $this->whenLoaded('region', function () {
                return [
                    'id' => $this->region?->id,
                    'code' => $this->region?->code,
                    'libelle' => $this->region?->libelle,
                ];
            }),

            'departement_id' => $this->departement_id,

            'departement' => $this->whenLoaded(
                'departement',
                function () {
                    return [
                        'id' => $this->departement?->id,
                        'code' => $this->departement?->code,
                        'libelle' => $this->departement?->libelle,
                        'region_id' => $this->departement?->region_id,
                    ];
                }
            ),

            'est_actif' => (bool) $this->est_actif,

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}