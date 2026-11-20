<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Parametrage\Region;
use Illuminate\Support\Facades\DB;

/**
 * Listes des menus deroulants du formulaire de convocation :
 * - regions : "Lieu d'affectation" et "Provenance" (president du jury,
 *   chef de centre, membres) ;
 * - disciplines : "Metier / specialite" des groupes metier.
 *
 * Seuls les libelles sont renvoyes : ces champs restent stockes en texte.
 */
class ConvocationReferentielsController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $regions = Region::query()
            ->where('est_actif', true)
            ->orderBy('libelle')
            ->pluck('libelle');

        // Pas de modele Discipline : lecture directe de la table.
        $disciplines = DB::table('disciplines')
            ->where(fn ($query) => $query->whereNull('statut')->orWhere('statut', 'actif'))
            ->orderBy('libelle')
            ->pluck('libelle');

        return $this->success('Référentiels des convocations.', [
            'regions' => $regions->filter()->unique()->values(),
            'disciplines' => $disciplines->filter()->unique()->values(),
        ]);
    }
}
