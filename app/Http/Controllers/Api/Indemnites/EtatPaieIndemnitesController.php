<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreEtatPaieIndemniteRequest;
use App\Http\Requests\Indemnites\UpdateEtatPaieIndemniteRequest;
use App\Http\Requests\Indemnites\GenererEtatPaieIndemniteRequest;
use App\Models\etat_paie_indemnites;
use App\Models\indemnites;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EtatPaieIndemnitesController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = etat_paie_indemnites::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $etats = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des états de paie des indemnités.', $etats);
    }

    public function store(StoreEtatPaieIndemniteRequest $request)
    {
        $data = $request->validated();
        $data['reference'] = 'EP-' . Str::upper(Str::random(8));
        $data['utilisateur_id'] = $request->user()?->id;
        $data['statut'] = 'brouillon';
        $data['verrouille'] = false;

        $etat = etat_paie_indemnites::create($data);

        return $this->success('État de paie créé avec succès.', $etat, 201);
    }

    public function show(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        return $this->success('État de paie trouvé.', $etat);
    }

    public function update(UpdateEtatPaieIndemniteRequest $request, string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        if ($etat->verrouille) {
            return $this->error('Cet état de paie est verrouillé et ne peut plus être modifié.', 422);
        }

        $etat->update($request->validated());

        return $this->success('État de paie mis à jour avec succès.', $etat);
    }

    /**
     * CORRECTIF : avant de supprimer un état de paie, on libère les
     * indemnités qui lui étaient rattachées (etat_paie_indemnite_id),
     * sinon elles resteraient bloquées indéfiniment et ne pourraient
     * plus jamais être intégrées à un futur état de paie.
     */
    public function destroy(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        if ($etat->verrouille) {
            return $this->error('Cet état de paie est verrouillé et ne peut pas être supprimé.', 422);
        }

        DB::transaction(function () use ($etat) {
            indemnites::where('etat_paie_indemnite_id', $etat->id)
                ->update(['etat_paie_indemnite_id' => null]);

            $etat->delete();
        });

        return $this->success('État de paie supprimé avec succès.');
    }

    public function individuels(Request $request)
    {
        $etats = etat_paie_indemnites::where('type', 'individuel')
            ->when($request->filled('beneficiaire_id'), fn ($q) => $q->where('beneficiaire_id', $request->query('beneficiaire_id')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success('États de paie individuels.', $etats);
    }

    public function consolides(Request $request)
    {
        $etats = etat_paie_indemnites::where('type', 'consolide')
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success('États de paie consolidés.', $etats);
    }

    public function export(Request $request)
    {
        $query = etat_paie_indemnites::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        $etats = $query->get();

        return $this->success('Export des états de paie.', [
            'total' => $etats->count(),
            'items' => $etats,
        ]);
    }

    public function historique(Request $request)
    {
        $etats = etat_paie_indemnites::whereIn('statut', ['valide', 'archive', 'transmis'])
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return $this->success('Historique des états de paie.', $etats);
    }

    /**
     * Génère les détails d'un état de paie à partir des indemnités validées
     * correspondant au périmètre demandé.
     *
     * CORRECTIF anti-double-paiement : seules les indemnités validées ET
     * pas encore rattachées à un état de paie (etat_paie_indemnite_id null)
     * sont éligibles. Elles sont verrouillées (lockForUpdate) puis marquées
     * comme consommées dans la même transaction que la génération, pour
     * éviter qu'une même indemnité soit tirée par deux générations
     * concurrentes ou par un futur état de paie.
     */
    public function generer(GenererEtatPaieIndemniteRequest $request, string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        DB::transaction(function () use ($request, $etat) {
            $query = indemnites::where('statut', 'valide')
                ->whereNull('etat_paie_indemnite_id')
                ->lockForUpdate();

            $utilisateurIds = $request->validated('perimetre.utilisateur_ids');
            if (! empty($utilisateurIds)) {
                $query->whereIn('utilisateur_id', $utilisateurIds);
            } elseif ($etat->beneficiaire_id) {
                $query->where('utilisateur_id', $etat->beneficiaire_id);
            }

            $indemnitesValidees = $query->get();

            indemnites::whereIn('id', $indemnitesValidees->pluck('id'))
                ->update(['etat_paie_indemnite_id' => $etat->id]);

            $etat->update([
                'details' => $indemnitesValidees->toArray(),
                'total_montant' => $indemnitesValidees->sum('montant_total'),
                'perimetre' => $request->validated('perimetre'),
                'date_generation' => now(),
                'statut' => 'genere',
            ]);
        });

        return $this->success('État de paie généré avec succès.', $etat->fresh());
    }

    public function preview(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        return $this->success('Aperçu de l\'état de paie.', $etat);
    }

    public function valider(Request $request, string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        $etat->update([
            'statut' => 'valide',
            'verrouille' => true,
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
        ]);

        return $this->success('État de paie validé avec succès.', $etat);
    }

    public function archiver(Request $request, string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        if ($etat->statut !== 'valide') {
            return $this->error('Seul un état de paie validé peut être archivé.', 422);
        }

        $etat->update([
            'statut' => 'archive',
            'archive_par' => $request->user()?->id,
            'archive_at' => now(),
        ]);

        return $this->success('État de paie archivé avec succès.', $etat);
    }

    /**
     * Marque l'état de paie comme transmis (ex: au système de paie / SICA).
     */
    public function transmettre(string $id)
    {
        $etat = etat_paie_indemnites::find($id);

        if (! $etat) {
            return $this->error('État de paie introuvable.', 404);
        }

        if ($etat->statut !== 'valide') {
            return $this->error('Seul un état de paie validé peut être transmis.', 422);
        }

        $etat->update([
            'statut' => 'transmis',
            'transmit_sica' => true,
        ]);

        return $this->success('État de paie transmis avec succès.', $etat);
    }
}