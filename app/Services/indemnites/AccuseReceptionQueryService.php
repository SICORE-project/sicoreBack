<?php

declare(strict_types=1);

namespace App\Services\indemnites;

use App\Models\indemnites\Accuse_reception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AccuseReceptionQueryService
{
    public function build(Request $request): Builder
    {
        $query = Accuse_reception::query()
            ->with([
                'document',
                'agentDeposant.user',
                'agentDeposant.lieuDeService',
                'agentReceptionnaire.user',
                'agentReceptionnaire.lieuDeService',
            ])
            ->nonArchives();

        if ($request->filled('search')) {
            $search = trim(
                (string) $request->input('search')
            );

            $query->where(function (Builder $query) use ($search): void {
                $query
                    ->whereHas('document', function (Builder $query) use ($search): void {
                        $query
                            ->where('reference', 'like', "%{$search}%")
                            ->orWhere('libelle', 'like', "%{$search}%");
                    })
                    ->orWhereHas('agentDeposant', function (Builder $query) use ($search): void {
                        $query->where('matricule', 'like', "%{$search}%")
                            ->orWhereHas('user', function (Builder $query) use ($search): void {
                                $query
                                    ->where('nom', 'like', "%{$search}%")
                                    ->orWhere('prenom', 'like', "%{$search}%");
                            });
                    })
                    ->orWhereHas('agentDeposant.lieuDeService', function (Builder $query) use ($search): void {
                        $query
                            ->where('code', 'like', "%{$search}%")
                            ->orWhere('libelle', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->input('status')
            );
        }

        return $query->latest('date_depot');
    }
}
