<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Parametrage\Enseignant;
use App\Models\Parametrage\StatutEnseignant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Recherche d'enseignants par matricule pour le formulaire de convocation
 * (president du jury, chef de centre, membres du jury) : des la saisie, on
 * propose les matricules qui COMMENCENT par le texte tape ; le choix d'un
 * matricule remplit prenom, nom et telephone.
 *
 * Tri par matricule : si le texte tape est un matricule complet, il arrive
 * en premier (une chaine est toujours triee avant celles qui la prolongent).
 */
class ConvocationEnseignantMatriculeController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $matricule = trim((string) $request->query('matricule', ''));

        if ($matricule === '') {
            return $this->success('Enseignants.', []);
        }

        // % et _ saisis sont cherches tels quels, pas comme jokers SQL.
        $prefixe = addcslashes($matricule, '\\%_').'%';

        $enseignants = Enseignant::query()
            ->visibleTo($request->user())
            ->actif()
            ->where('matricule', 'like', $prefixe)
            ->with('statutEnseignant:id,code,libelle,categorie')
            ->orderBy('matricule')
            ->limit(10)
            ->get(['id', 'matricule', 'nom', 'prenom', 'telephone', 'email', 'statut_enseignant_id'])
            ->map(fn (Enseignant $enseignant) => [
                'id' => $enseignant->id,
                'matricule' => $enseignant->matricule,
                'nom' => $enseignant->nom,
                'prenom' => $enseignant->prenom,
                'telephone' => $enseignant->telephone,
                'email' => $enseignant->email,
                'categorie_personnel' => $this->categoriePersonnel($enseignant->statutEnseignant),
            ]);

        return $this->success('Enseignants.', $enseignants);
    }

    /**
     * Categorie de personnel du formulaire de convocation (valeurs du
     * <select> : fonctionnaire / contractuel / vacataire), deduite du statut
     * de l'enseignant (table statuts_enseignant) : par son code, a defaut
     * par sa colonne categorie ou son libelle. Null si rien ne correspond.
     */
    private function categoriePersonnel(?StatutEnseignant $statut): ?string
    {
        if (! $statut) {
            return null;
        }

        foreach ([$statut->code, $statut->categorie, $statut->libelle] as $valeur) {
            $valeur = Str::lower(Str::ascii(trim((string) $valeur)));

            foreach (['fonctionnaire', 'contractuel', 'vacataire'] as $categorie) {
                if ($valeur !== '' && str_starts_with($valeur, $categorie)) {
                    return $categorie;
                }
            }
        }

        return null;
    }
}
