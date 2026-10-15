<?php

namespace App\Http\Controllers\Api\Indemnites\Concerns;

use App\Models\Indemnite\Convocations;
use Illuminate\Http\JsonResponse;

/**
 * Les pièces justificatives sont fournies après les services faits (après les examens) :
 * le dépôt est refusé tant que la date de fin de la convocation n'est pas passée.
 */
trait RefuseDepotAvantFinExamen
{
    protected function refusDepot(mixed $convocationId): ?JsonResponse
    {
        if (! config('indemnites.depot_apres_examen', true) || ! $convocationId) {
            return null;
        }

        $convocation = Convocations::find($convocationId);

        if (! $convocation || ! $convocation->date_fin) {
            return null;
        }

        if (now()->startOfDay()->gt($convocation->date_fin->copy()->startOfDay())) {
            return null;
        }

        return $this->error(
            'Les pièces justificatives sont fournies après les services faits : le dépôt sera possible à partir du '
            . $convocation->date_fin->copy()->addDay()->format('d/m/Y') . ', une fois l\'examen terminé.',
            422
        );
    }
}
