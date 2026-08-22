<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LieuServiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'libelle' => $this->libelle,
            'type' => $this->type,
            'adresse' => $this->adresse,
            'est_actif' => $this->est_actif,
            'ia_id' => $this->ia_id,
            'ief_id' => $this->ief_id,
            'ia' => $this->whenLoaded('ia', fn () => $this->ia ? [
                'id' => $this->ia->id,
                'code' => $this->ia->code,
                'libelle' => $this->ia->libelle,
            ] : null),
            'ief' => $this->whenLoaded('ief', fn () => $this->ief ? [
                'id' => $this->ief->id,
                'code' => $this->ief->code,
                'libelle' => $this->ief->libelle,
                'ia_id' => $this->ief->ia_id,
            ] : null),
            'hierarchie_coherente' => $this->hierarchie_coherente,
        ];
    }
}
