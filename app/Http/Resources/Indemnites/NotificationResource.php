<?php

namespace App\Http\Resources\Indemnites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
        ];
    }

}
