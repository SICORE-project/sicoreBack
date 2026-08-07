<?php

namespace App\Http\Controllers;

use App\Models\BaremeDeplacement;
use App\Models\JustificatifFraisDeplacement;
use App\Models\LigneFraisDeplacement;
use App\Models\MissionDeplacement;
use App\Notifications\FraisDeplacementTraite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FraisDeplacementController extends Controller
{
    public function index(Request $request)
    {
        $query = MissionDeplacement::with(['beneficiaire', 'declarant', 'lignes.bareme']);
        if (! $this->isAdmin($request)) $query->where('declare_par', $request->user()->id);
        foreach (['statut', 'beneficiaire_id'] as $filter) if ($request->filled($filter)) $query->where($filter, $request->input($filter));
        return response()->json(['success' => true, 'data' => $query->latest()->paginate($this->perPage($request))]);
    }

    public function enAttente(Request $request)
    {
        $this->admin($request);
        return response()->json(['success' => true, 'data' => MissionDeplacement::with(['beneficiaire', 'declarant', 'lignes.justificatifs'])->where('statut', 'en_attente')->latest()->paginate($this->perPage($request))]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->missionRules());
        $data['declare_par'] = $request->user()->id;
        $data['reference'] = 'MD-'.now()->format('Ymd').'-'.strtoupper(Str::random(6));
        $mission = MissionDeplacement::create($data);
        return response()->json(['success' => true, 'data' => $mission], 201);
    }

    public function show(Request $request, MissionDeplacement $mission)
    {
        $this->access($request, $mission);
        return response()->json(['success' => true, 'data' => $mission->load(['beneficiaire', 'declarant', 'lignes.bareme', 'lignes.justificatifs', 'justificatifs'])]);
    }

    public function update(Request $request, MissionDeplacement $mission)
    {
        $this->owner($request, $mission);
        abort_unless(in_array($mission->statut, ['brouillon', 'rejetee'], true), 422, 'Cette déclaration ne peut plus être modifiée.');
        $data = $request->validate($this->missionRules(true));
        $mission->update($data);
        return response()->json(['success' => true, 'data' => $mission->fresh()]);
    }

    public function ajouterLigne(Request $request, MissionDeplacement $mission)
    {
        $this->owner($request, $mission);
        abort_unless(in_array($mission->statut, ['brouillon', 'rejetee'], true), 422, 'Les frais ne peuvent plus être modifiés.');
        $data = $request->validate(['type_frais' => ['required', 'in:kilometrage,hebergement,repas'], 'quantite' => ['nullable', 'numeric', 'gt:0'], 'montant_declare' => ['nullable', 'numeric', 'min:0'], 'description' => ['nullable', 'string', 'max:2000']]);
        $quantite = $data['quantite'] ?? ($data['type_frais'] === 'kilometrage' ? $mission->distance_km : 1);
        abort_if($quantite === null, 422, 'La distance de la mission est requise pour calculer le kilométrage.');
        $line = $mission->lignes()->create(array_merge($data, ['quantite' => $quantite]));
        return response()->json(['success' => true, 'data' => $line], 201);
    }

    public function supprimerLigne(Request $request, MissionDeplacement $mission, LigneFraisDeplacement $ligne)
    {
        $this->owner($request, $mission);
        abort_unless($ligne->mission_id === $mission->id, 404);
        abort_unless(in_array($mission->statut, ['brouillon', 'rejetee'], true), 422, 'Les frais ne peuvent plus être modifiés.');
        $ligne->delete();
        $this->total($mission);
        return response()->json(['success' => true, 'message' => 'Ligne supprimée.']);
    }

    public function calculer(Request $request, MissionDeplacement $mission)
    {
        $this->owner($request, $mission);
        abort_unless(in_array($mission->statut, ['brouillon', 'rejetee'], true), 422, 'Le calcul est verrouillé.');
        abort_if($mission->lignes()->doesntExist(), 422, 'Ajoutez au moins une ligne de frais.');
        DB::transaction(function () use ($mission) {
            $mission->lignes()->each(function (LigneFraisDeplacement $ligne) use ($mission) {
                $bareme = $this->baremeApplicable($mission, $ligne->type_frais);
                abort_unless($bareme, 422, "Aucun barème applicable pour {$ligne->type_frais}.");
                $brut = round((float) $ligne->quantite * (float) $bareme->taux_unitaire, 2);
                $calcule = $bareme->plafond === null ? $brut : min($brut, (float) $bareme->plafond);
                $ligne->update(['bareme_id' => $bareme->id, 'taux_unitaire' => $bareme->taux_unitaire, 'montant_calcule' => $calcule, 'plafond_applique' => $bareme->plafond, 'justificatif_obligatoire' => $bareme->justificatif_obligatoire]);
            });
            $this->total($mission);
        });
        return response()->json(['success' => true, 'data' => $mission->fresh()->load('lignes.bareme')]);
    }

    public function calculerForfaitReglementaire(Request $request, MissionDeplacement $mission)
    {
        $this->owner($request, $mission);
        abort_unless(in_array($mission->statut, ['brouillon', 'rejetee'], true), 422, 'Le calcul est verrouillé.');
        $data = $request->validate(['statut_agent' => ['required', 'in:fonctionnaire,vacataire,contractuel'], 'indice_agent' => ['nullable', 'integer', 'min:0', 'required_if:statut_agent,fonctionnaire'], 'salaire_global_annuel' => ['nullable', 'numeric', 'min:0', 'required_unless:statut_agent,fonctionnaire'], 'lieu_service' => ['required', 'string', 'max:255']]);
        $montant = $data['statut_agent'] === 'fonctionnaire'
            ? ($data['indice_agent'] <= 1728 ? 15000 : ($data['indice_agent'] <= 2295 ? 20000 : 25000))
            : ($data['salaire_global_annuel'] > 2429500 ? 25000 : ($data['salaire_global_annuel'] >= 2109610 ? 20000 : 15000));
        if (mb_strtolower(trim($data['lieu_service'])) === mb_strtolower(trim($mission->lieu_destination))) $montant /= 4;
        $mission->update($data);
        $ligne = $mission->lignes()->firstOrNew(['description' => 'Forfait de déplacement réglementaire']);
        $ligne->fill(['type_frais' => 'kilometrage', 'quantite' => 1, 'taux_unitaire' => $montant, 'montant_calcule' => $montant, 'montant_declare' => $montant, 'plafond_applique' => $montant, 'justificatif_obligatoire' => false]);
        $ligne->save(); $this->total($mission);
        return response()->json(['success' => true, 'data' => ['montant' => $montant, 'regle' => $data['statut_agent'] === 'fonctionnaire' ? 'Barème fonctionnaire par indice' : 'Barème vacataire/contractuel par salaire global annuel', 'mission' => $mission->fresh()->load('lignes')]]);
    }

    public function deposerJustificatif(Request $request, MissionDeplacement $mission)
    {
        $this->owner($request, $mission);
        $data = $request->validate(['ligne_frais_id' => ['nullable', 'integer', 'exists:lignes_frais_deplacement,id'], 'fichier' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240']]);
        if (isset($data['ligne_frais_id'])) abort_unless($mission->lignes()->whereKey($data['ligne_frais_id'])->exists(), 422, 'La ligne ne correspond pas à cette mission.');
        $file = $data['fichier']; $path = $file->store("frais-deplacement/{$mission->id}", 'local');
        $piece = $mission->justificatifs()->create(['ligne_frais_id' => $data['ligne_frais_id'] ?? null, 'nom_original' => $file->getClientOriginalName(), 'chemin' => $path, 'mime_type' => $file->getMimeType(), 'taille' => $file->getSize(), 'depose_par' => $request->user()->id]);
        return response()->json(['success' => true, 'data' => $piece], 201);
    }

    public function verifierJustificatif(Request $request, MissionDeplacement $mission, JustificatifFraisDeplacement $justificatif)
    {
        $this->admin($request); abort_unless($justificatif->mission_id === $mission->id, 404);
        $data = $request->validate(['conforme' => ['required', 'boolean'], 'commentaire' => ['nullable', 'string', 'max:2000']]);
        $justificatif->update($data + ['verifie_par' => $request->user()->id, 'verifie_at' => now()]);
        return response()->json(['success' => true, 'data' => $justificatif->fresh()]);
    }

    public function soumettre(Request $request, MissionDeplacement $mission)
    {
        $this->owner($request, $mission);
        abort_unless(in_array($mission->statut, ['brouillon', 'rejetee'], true), 422, 'Cette déclaration a déjà été soumise.');
        abort_if($mission->lignes()->doesntExist(), 422, 'Ajoutez au moins une ligne de frais.');
        abort_if((float) $mission->montant_calcule <= 0, 422, 'Calculez les frais avant soumission.');
        $missing = $mission->lignes()->where('justificatif_obligatoire', true)->whereDoesntHave('justificatifs')->exists();
        abort_if($missing, 422, 'Des justificatifs obligatoires sont manquants.');
        $mission->update(['statut' => 'en_attente', 'motif_rejet' => null]);
        return response()->json(['success' => true, 'message' => 'Déclaration transmise pour validation.', 'data' => $mission->fresh()]);
    }

    public function valider(Request $request, MissionDeplacement $mission)
    {
        $this->admin($request); abort_unless($mission->statut === 'en_attente', 422, 'La déclaration doit être en attente.');
        $missing = $mission->lignes()->where('justificatif_obligatoire', true)->whereDoesntHave('justificatifs', fn ($q) => $q->where('conforme', true))->exists();
        abort_if($missing, 422, 'Les justificatifs obligatoires doivent être vérifiés conformes.');
        $data = $request->validate(['montant_approuve' => ['nullable', 'numeric', 'min:0'], 'echeance_paiement' => ['nullable', 'date', 'after_or_equal:today']]);
        $total = $data['montant_approuve'] ?? $mission->montant_calcule;
        $mission->update(['statut' => 'remboursement_en_attente', 'montant_approuve' => $total, 'valide_par' => $request->user()->id, 'valide_at' => now(), 'echeance_paiement' => $data['echeance_paiement'] ?? now()->addDays(30)->toDateString(), 'motif_rejet' => null]);
        $mission->declarant->notify(new FraisDeplacementTraite($mission->fresh()));
        return response()->json(['success' => true, 'data' => $mission->fresh()]);
    }

    public function rejeter(Request $request, MissionDeplacement $mission)
    {
        $this->admin($request); abort_unless($mission->statut === 'en_attente', 422, 'La déclaration doit être en attente.');
        $data = $request->validate(['motif_rejet' => ['required', 'string', 'min:3', 'max:2000']]);
        $mission->update(['statut' => 'rejetee', 'motif_rejet' => $data['motif_rejet'], 'valide_par' => $request->user()->id, 'valide_at' => now()]);
        $mission->declarant->notify(new FraisDeplacementTraite($mission->fresh()));
        return response()->json(['success' => true, 'data' => $mission->fresh()]);
    }

    public function remboursement(Request $request, MissionDeplacement $mission)
    {
        $this->access($request, $mission);
        return response()->json(['success' => true, 'data' => ['statut' => $mission->statut, 'montant' => $mission->montant_approuve, 'echeance_paiement' => $mission->echeance_paiement, 'rembourse_le' => $mission->rembourse_le, 'en_retard' => $mission->statut === 'remboursement_en_attente' && $mission->echeance_paiement?->isPast()]]);
    }

    public function relancer(Request $request, MissionDeplacement $mission)
    {
        $this->admin($request); abort_unless($mission->statut === 'remboursement_en_attente' && $mission->echeance_paiement?->isPast(), 422, 'Aucune relance n’est nécessaire.');
        $mission->update(['relance_at' => now(), 'notification_at' => now(), 'notification_message' => 'Relance envoyée pour remboursement en retard.']);
        return response()->json(['success' => true, 'data' => $mission->fresh()]);
    }

    public function cloturer(Request $request, MissionDeplacement $mission)
    {
        $this->admin($request); abort_unless($mission->statut === 'remboursement_en_attente', 422, 'Ce remboursement ne peut pas être clôturé.');
        $mission->update(['statut' => 'remboursee', 'rembourse_le' => now(), 'rembourse_par' => $request->user()->id]);
        return response()->json(['success' => true, 'data' => $mission->fresh()]);
    }

    public function baremes(Request $request) { $this->admin($request); return response()->json(['success' => true, 'data' => BaremeDeplacement::latest('date_effet')->paginate($this->perPage($request))]); }
    public function creerBareme(Request $request) { $this->admin($request); $bareme = BaremeDeplacement::create($request->validate($this->baremeRules()) + ['cree_par' => $request->user()->id]); return response()->json(['success' => true, 'data' => $bareme], 201); }
    public function modifierBareme(Request $request, BaremeDeplacement $bareme) { $this->admin($request); $bareme->update($request->validate($this->baremeRules(true))); return response()->json(['success' => true, 'data' => $bareme->fresh()]); }

    private function baremeApplicable(MissionDeplacement $mission, string $type): ?BaremeDeplacement { return BaremeDeplacement::where('type_frais', $type)->where('actif', true)->whereDate('date_effet', '<=', $mission->date_depart)->where(fn ($q) => $q->whereNull('date_fin')->orWhereDate('date_fin', '>=', $mission->date_depart))->where(fn ($q) => $q->whereNull('zone')->orWhere('zone', $mission->lieu_destination))->where(fn ($q) => $q->whereNull('moyen_transport')->orWhere('moyen_transport', $mission->moyen_transport))->orderByRaw('zone is not null desc')->orderByRaw('moyen_transport is not null desc')->latest('date_effet')->first(); }
    private function total(MissionDeplacement $mission): void { $mission->update(['montant_calcule' => $mission->lignes()->sum('montant_calcule')]); }
    private function missionRules(bool $partial = false): array { $p = $partial ? 'sometimes' : 'required'; return ['beneficiaire_id' => [$p, 'exists:utilisateurs,id'], 'lieu_depart' => ['nullable', 'string', 'max:255'], 'lieu_destination' => [$p, 'string', 'max:255'], 'motif' => [$p, 'string', 'max:2000'], 'date_depart' => [$p, 'date'], 'date_retour' => [$p, 'date', 'after_or_equal:date_depart'], 'distance_km' => ['nullable', 'numeric', 'min:0'], 'moyen_transport' => ['nullable', 'string', 'max:100']]; }
    private function baremeRules(bool $partial = false): array { $p = $partial ? 'sometimes' : 'required'; return ['libelle' => [$p, 'string', 'max:255'], 'type_frais' => [$p, 'in:kilometrage,hebergement,repas'], 'zone' => ['nullable', 'string', 'max:100'], 'moyen_transport' => ['nullable', 'string', 'max:100'], 'taux_unitaire' => [$p, 'numeric', 'min:0'], 'plafond' => ['nullable', 'numeric', 'min:0'], 'justificatif_obligatoire' => ['nullable', 'boolean'], 'date_effet' => [$p, 'date'], 'date_fin' => ['nullable', 'date', 'after_or_equal:date_effet'], 'actif' => ['nullable', 'boolean']]; }
    private function isAdmin(Request $request): bool { return strtolower((string) $request->user()->loadMissing('role')->role?->libelle) === 'administrateur'; }
    private function admin(Request $request): void { abort_unless($this->isAdmin($request), 403, 'Réservé aux administrateurs.'); }
    private function access(Request $request, MissionDeplacement $mission): void { abort_unless($this->isAdmin($request) || $mission->declare_par === $request->user()->id || $mission->beneficiaire_id === $request->user()->id, 403, 'Accès non autorisé.'); }
    private function owner(Request $request, MissionDeplacement $mission): void { abort_unless($mission->declare_par === $request->user()->id, 403, 'Seul le déclarant peut effectuer cette action.'); }
    private function perPage(Request $request): int { return min(max($request->integer('per_page', 15), 1), 100); }
}
