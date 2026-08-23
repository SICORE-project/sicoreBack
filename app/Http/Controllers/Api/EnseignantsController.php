<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
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
        $query = Enseignant::query()->actif();

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
     * Coordonnées bancaires d'UN enseignant — alimente la modale "Ajouter
     * état de paie" (code banque/guichet/numéro de compte/clé RIB déjà
     * enregistrés sur sa fiche, pré-remplis puis modifiables). Volontairement
     * absent de index() (select() minimal, pas de RIB dans une liste de
     * recherche) : un enregistrement précis, demandé explicitement par id.
     */
    public function show(string $id)
    {
        $enseignant = Enseignant::query()
            ->select(['id', 'matricule', 'nom', 'prenom', 'code_banque', 'code_guichet', 'numero_compte_bancaire', 'cle_rib', 'titulaire_compte'])
            ->find($id);

        if (! $enseignant) {
            return $this->error('Enseignant introuvable.', 404);
        }

        return $this->success('Enseignant trouvé.', $enseignant);
    }
}
