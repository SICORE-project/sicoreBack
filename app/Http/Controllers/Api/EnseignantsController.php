<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\Parametrage\Enseignant;
use Illuminate\Http\Request;

/**
 * Endpoint minimal, en attendant le module Enseignants complet (cf.
 * routes/web.php, actuellement commente et gere par une autre equipe).
 *
 * Sert uniquement a alimenter la recherche de beneficiaires depuis le
 * formulaire de convocation (nom, prenom, telephone) : pas de CRUD ici.
 */
class EnseignantsController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = Enseignant::query()->visibleTo($request->user())->actif();

        if ($request->filled('search')) {
            $query->search($request->query('search'));
        }

        $enseignants = $query
            ->select(['id', 'matricule', 'nom', 'prenom', 'telephone', 'email', 'categorie_personnel'])
            ->orderBy('nom')
            ->paginate($request->integer('per_page', 15));

        return $this->success('Liste des enseignants.', $enseignants);
    }

    /**
     * Coordonnées bancaires de plusieurs enseignants (compte actif, principal en priorité ;
     * à défaut les champs de la fiche enseignant). Sert à la génération des états de paie.
     */
    public function coordonneesBancaires(Request $request)
    {
        $ids = array_values(array_filter(array_map('intval', (array) $request->query('ids', []))));

        $requete = Enseignant::query()
            ->whereIn('id', $ids)
            ->with(['comptesBancaires' => fn ($q) => $q->where('est_actif', true)]);

        // Un utilisateur affecté à une IA ou un IEF ne voit que les enseignants de son périmètre.
        foreach ($request->user()?->perimetre() ?? [] as $colonne => $valeur) {
            $valeur ? $requete->where($colonne, $valeur) : $requete->whereRaw('1 = 0');
        }

        $enseignants = $requete->get();

        $data = [];

        foreach ($enseignants as $enseignant) {
            $compte = $enseignant->comptesBancaires->sortByDesc('est_principal')->first();

            $rib = [
                'code_banque' => $compte?->code_banque ?: $enseignant->code_banque,
                'code_guichet' => $compte?->code_guichet ?: $enseignant->code_guichet,
                'numero_compte_bancaire' => $compte?->numero_compte ?: $enseignant->numero_compte_bancaire,
                'cle_rib' => $compte?->cle_rib ?: $enseignant->cle_rib,
            ];

            $rib['complet'] = collect($rib)->every(fn ($valeur) => filled($valeur));
            $data[$enseignant->id] = $rib;
        }

        return $this->success('Coordonnées bancaires.', $data);
    }
}
