<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiplomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'libelle'         => $this->libelle,
            'categorie_id'    => $this->categorie_id,
            'categorie'       => $this->whenLoaded('categorie', fn () => $this->categorie ? [
                'id' => $this->categorie->id,
                'libelle' => $this->categorie->libelle,
            ] : null),
            'salaire_brut'    => (float) $this->salaire_brut,
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
