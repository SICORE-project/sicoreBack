<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class IaResource extends JsonResource
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
                    'id' => $this->region->id,
                    'code' => $this->region->code,
                    'nom' => $this->region->nom,
                ];
            }),

        ];
    }
}
