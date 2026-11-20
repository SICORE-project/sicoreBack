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
        $lieux = LieuService::query()
            ->etablissements()
            ->where('est_actif', true)
            ->orderBy('libelle')
            ->get(['id', 'code', 'libelle']);

        return $this->success('Lieux de service.', $lieux);
    }
}
