<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'chef_lieu' => $this->chef_lieu,
            'superficie' => $this->superficie,
            'population' => $this->population,
            'est_actif' => (bool) $this->est_actif,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}