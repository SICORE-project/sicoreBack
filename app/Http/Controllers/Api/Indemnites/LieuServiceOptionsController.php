<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Parametrage\LieuService;

/**
 * Liste simple (non paginee) des lieux de service actifs, pour alimenter le
 * <select> "Centre d'examen" du formulaire de convocation. Route a part (et
 * non parametrage/lieux-service) car les profils Indemnites n'ont pas la
 * permission parametrage.lieux_service.read.
 */
class LieuServiceOptionsController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        // Etablissements uniquement (meme regle que la page Etablissements,
        // LieuServiceController::etablissements()) : exclut les structures
        // administratives (DRH, DECPC, DAGE, CI, IA, IEF). Un etablissement
        // rattache a une IEF a le type "IEF" mais un code genere "ETAB...".
        $lieux = LieuService::query()
            ->where(fn ($query) => $query->whereNull('type')
                ->orWhereRaw('UPPER(TRIM(type)) NOT IN (?, ?, ?, ?, ?, ?)', ['DRH', 'DECPC', 'DAGE', 'CI', 'IA', 'IEF'])
                ->orWhere(fn ($query) => $query->whereRaw('UPPER(TRIM(type)) = ?', ['IEF'])
                    ->where('code', 'like', 'ETAB%')))
            ->whereNotIn('code', ['DRH', 'DECPC', 'DAGE', 'CI'])
            ->where('est_actif', true)
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle']);

        return $this->success('Lieux de service.', $lieux);
    }
}
