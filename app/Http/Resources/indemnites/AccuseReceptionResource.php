<?php

declare(strict_types=1);

namespace App\Http\Resources\indemnites;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AccuseReceptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'document' => $this->document,

            'agent_deposant' => $this->agentDeposant,

            'agent_receptionnaire' => $this->agentReceptionnaire,

            'status' => $this->status?->value,

            'date_depot' => $this->date_depot?->toISOString(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
