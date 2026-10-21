<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartementResource extends JsonResource
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

            'est_actif' => (bool) $this->est_actif,

            'created_at' => $this->created_at?->toISOString(),

            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}