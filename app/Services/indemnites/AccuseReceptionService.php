<?php

declare(strict_types=1);

namespace App\Services\indemnites;

use App\Enums\indemnites\AccuseReceptionStatus;
use App\Models\indemnites\Accuse_reception;
use App\Models\indemnites\Agent;
use App\Models\indemnites\Document;
use Illuminate\Support\Facades\DB;

class AccuseReceptionService
{
    public function create(
        Document $document,
        Agent $agentDeposant,
        Agent $agentReceptionnaire,
        string $dateDepot,
        AccuseReceptionStatus $status
    ): Accuse_reception {
        return DB::transaction(
            function () use (
                $document,
                $agentDeposant,
                $agentReceptionnaire,
                $dateDepot,
                $status
            ): Accuse_reception {
                return Accuse_reception::create([
                    'document_id' => $document->id,
                    'agent_deposant_id' => $agentDeposant->id,
                    'agent_receptionnaire_id' => $agentReceptionnaire->id,
                    'date_depot' => $dateDepot,
                    'status' => $status,
                ]);
            }
        );
    }

    public function update(
        Accuse_reception $accuse,
        Document $document,
        Agent $agentDeposant,
        Agent $agentReceptionnaire,
        string $dateDepot,
        AccuseReceptionStatus $status
    ): Accuse_reception {
        return DB::transaction(
            function () use (
                $accuse,
                $document,
                $agentDeposant,
                $agentReceptionnaire,
                $dateDepot,
                $status
            ): Accuse_reception {
                $accuse->update([
                    'document_id' => $document->id,
                    'agent_deposant_id' => $agentDeposant->id,
                    'agent_receptionnaire_id' => $agentReceptionnaire->id,
                    'date_depot' => $dateDepot,
                    'status' => $status,
                ]);

                return $accuse->refresh();
            }
        );
    }

    public function delete(Accuse_reception $accuse): void
    {
        DB::transaction(
            function () use ($accuse): void {
                $accuse->delete();
            }
        );
    }
}
