<?php

declare(strict_types=1);

namespace App\Exports\indemnites;

use App\Services\indemnites\AccuseReceptionQueryService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class AccusesReceptionExport implements
    FromQuery,
    WithHeadings,
    WithMapping
{
    public function __construct(
        private readonly Request $request,
        private readonly AccuseReceptionQueryService $queryService
    ) {
    }

    public function query(): Builder
    {
        return $this->queryService
            ->build($this->request)
            ->withoutEagerLoads();
    }

    public function headings(): array
    {
        return [
            'ID',
            'Référence',
            'Document',
            'Matricule déposant',
            'Déposant',
            'Lieu déposant',
            'Matricule réceptionnaire',
            'Réceptionnaire',
            'Lieu de réception',
            'Statut',
            'Date de dépôt',
        ];
    }

    public function map($accuse): array
    {
        $accuse->loadMissing([
            'document',
            'agentDeposant.user',
            'agentDeposant.lieuDeService',
            'agentReceptionnaire.user',
            'agentReceptionnaire.lieuDeService',
        ]);

        return [
            $accuse->id,
            $accuse->document?->reference,
            $accuse->document?->libelle,

            $accuse->agentDeposant?->matricule,

            trim(
                ($accuse->agentDeposant?->user?->prenom ?? '')
                . ' '
                . ($accuse->agentDeposant?->user?->nom ?? '')
            ),

            $accuse->agentDeposant?->lieuDeService?->libelle,

            $accuse->agentReceptionnaire?->matricule,

            trim(
                ($accuse->agentReceptionnaire?->user?->prenom ?? '')
                . ' '
                . ($accuse->agentReceptionnaire?->user?->nom ?? '')
            ),

            $accuse->agentReceptionnaire?->lieuDeService?->libelle,

            $accuse->status?->label(),

            $accuse->date_depot?->format('d/m/Y H:i'),
        ];
    }
}
