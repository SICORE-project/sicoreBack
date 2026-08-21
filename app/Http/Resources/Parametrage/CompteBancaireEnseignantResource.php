<?php

namespace App\Http\Resources\Parametrage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompteBancaireEnseignantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'enseignant_id' => $this->enseignant_id,
            'institut_financier_id' => $this->institut_financier_id,
            'numero_compte' => $this->numero_compte,
            'rib' => $this->rib,
            'est_actif' => $this->est_actif,
            'institution_financiere' => new InstitutFinancierResource($this->whenLoaded('institutionFinanciere')),
        ];
    }
}
