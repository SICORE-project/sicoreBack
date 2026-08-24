<?php

namespace App\Http\Resources\Indemnites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\indemnite\Convocations;

class NotificationDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $pivot = $this->users->first()?->pivot;

        return [
            'id'      => $this->id,
            'titre'   => $this->titre,
            'message' => $this->message,
            'type'    => $this->type,
            'url'     => $this->url,
            'lu'      => (bool) ($pivot?->est_lu ?? false),
            'lu_at'   => $pivot?->lu_at,
            'date'    => $this->created_at->diffForHumans(),

            'created_by' => $this->createdBy ? [
                'id'  => $this->createdBy->id,
                'nom' => trim("{$this->createdBy->prenom} {$this->createdBy->nom}"),
            ] : null,

            'sujet' => $this->when($this->sujet !== null, fn () => $this->formatSujet()),
        ];
    }

    protected function formatSujet(): ?array
    {
        if ($this->sujet_type === Convocations::class) {
            return [
                'type'       => 'convocation',
                'id'         => $this->sujet->id,
                'objet'      => $this->sujet->objet,
                'statut'     => $this->sujet->statut,
                'date_debut' => $this->sujet->date_debut?->format('d/m/Y'),
                'date_fin'   => $this->sujet->date_fin?->format('d/m/Y'),
            ];
        }

        return null;
    }

}
