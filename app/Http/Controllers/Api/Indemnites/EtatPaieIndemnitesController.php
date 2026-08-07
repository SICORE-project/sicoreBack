<?php

namespace App\Http\Controllers;

use App\Models\etat_paie_indemnites;
use App\Models\indemnites;
use App\Models\MissionDeplacement;
use App\Models\utilisateurs;
use App\Services\XlsxExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;

class EtatPaieIndemnitesController extends Controller
{
    public function index(Request $request)
    {
        $this->admin($request);
        $query = etat_paie_indemnites::with(['beneficiaire', 'generateur', 'validateur']);
        foreach (['type', 'statut', 'beneficiaire_id', 'periode_debut', 'periode_fin', 'lieu_examen', 'session'] as $filter) if ($request->filled($filter)) $query->where($filter, $request->input($filter));
        return response()->json(['success' => true, 'data' => $query->latest()->paginate($this->perPage($request))]);
    }

    public function genererIndividuel(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['beneficiaire_id' => ['required', 'exists:utilisateurs,id'], 'periode_debut' => ['required', 'date'], 'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'], 'centre' => ['nullable', 'string', 'max:255'], 'session' => ['nullable', 'string', 'max:100']]);
        $elements = $this->elements($data['periode_debut'], $data['periode_fin'], [$data['beneficiaire_id']]);
        $etat = $this->creerEtat($request, 'individuel', $data['periode_debut'], $data['periode_fin'], $elements, $data['beneficiaire_id'], ['beneficiaires' => [$data['beneficiaire_id']]], $data['centre'] ?? null, $data['session'] ?? null);
        return response()->json(['success' => true, 'data' => $etat->load('beneficiaire')], 201);
    }

    public function genererConsolide(Request $request)
    {
        $this->admin($request);
        $data = $request->validate(['periode_debut' => ['required', 'date'], 'periode_fin' => ['required', 'date', 'after_or_equal:periode_debut'], 'beneficiaires' => ['nullable', 'array', 'min:1'], 'beneficiaires.*' => ['integer', 'exists:utilisateurs,id'], 'centre' => ['nullable', 'string', 'max:255'], 'session' => ['nullable', 'string', 'max:100']]);
        $beneficiaires = $data['beneficiaires'] ?? null;
        $elements = $this->elements($data['periode_debut'], $data['periode_fin'], $beneficiaires);
        $etat = $this->creerEtat($request, 'consolide', $data['periode_debut'], $data['periode_fin'], $elements, null, ['beneficiaires' => $beneficiaires], $data['centre'] ?? null, $data['session'] ?? null);
        return response()->json(['success' => true, 'data' => $etat], 201);
    }

    public function show(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request);
        return response()->json(['success' => true, 'data' => $etat->load(['beneficiaire', 'generateur', 'validateur'])]);
    }

    public function coherence(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request);
        $somme = collect($etat->details['elements'] ?? [])->sum('montant');
        return response()->json(['success' => round((float) $somme, 2) === round((float) $etat->total_montant, 2), 'data' => ['total_etat' => $etat->total_montant, 'total_elements' => round($somme, 2), 'ecart' => round($etat->total_montant - $somme, 2), 'nombre_elements' => count($etat->details['elements'] ?? [])]]);
    }

    public function valider(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request);
        abort_unless(in_array($etat->statut, ['genere', 'a_corriger'], true) && ! $etat->verrouille, 422, 'Cet état ne peut plus être validé.');
        $somme = collect($etat->details['elements'] ?? [])->sum('montant');
        abort_unless(round((float) $somme, 2) === round((float) $etat->total_montant, 2), 422, 'Les totaux de l’état sont incohérents.');
        $etat->update(['statut' => 'valide', 'verrouille' => true, 'valide_par' => $request->user()->id, 'valide_at' => now(), 'commentaire_correction' => null]);
        return response()->json(['success' => true, 'data' => $etat->fresh()]);
    }

    public function renvoyerCorrection(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request);
        abort_unless($etat->statut === 'genere' && ! $etat->verrouille, 422, 'Seul un état généré peut être renvoyé en correction.');
        $data = $request->validate(['commentaire' => ['required', 'string', 'min:3', 'max:2000']]);
        $etat->update(['statut' => 'a_corriger', 'commentaire_correction' => $data['commentaire']]);
        return response()->json(['success' => true, 'data' => $etat->fresh()]);
    }

    public function exporter(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request);
        $format = $request->validate(['format' => ['required', 'in:pdf,excel']])['format'];
        $etat->load('beneficiaire');
        if ($format === 'pdf') return Pdf::loadView('etats-paie.document', compact('etat'))->download("{$etat->reference}.pdf");
        $rows = [['Bénéficiaire', 'Nature', 'Référence', 'Date', 'Montant']];
        foreach ($etat->details['elements'] ?? [] as $element) $rows[] = [$element['beneficiaire'], $element['source'] === 'indemnite' ? 'Indemnité' : 'Frais de déplacement', $element['libelle'], $element['date'], $element['montant']];
        $rows[] = ['', '', '', 'Total', $etat->total_montant];
        $path = app(XlsxExportService::class)->create("{$etat->reference}.xlsx", $rows);
        return response()->download($path, "{$etat->reference}.xlsx", ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])->deleteFileAfterSend(true);
    }

    public function previsualiser(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request); $etat->load('beneficiaire');
        return Pdf::loadView('etats-paie.document', compact('etat'))->stream("{$etat->reference}.pdf");
    }

    public function archiver(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request); abort_unless($etat->statut === 'valide' && $etat->verrouille, 422, 'Seul un état validé et verrouillé peut être archivé.');
        $etat->update(['statut' => 'archive', 'archive_par' => $request->user()->id, 'archive_at' => now()]);
        return response()->json(['success' => true, 'data' => $etat->fresh()]);
    }

    public function historique(Request $request)
    {
        $this->admin($request);
        return response()->json(['success' => true, 'data' => etat_paie_indemnites::with('beneficiaire')->where('statut', 'archive')->latest('archive_at')->paginate($this->perPage($request))]);
    }

    public function restaurer(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request); abort_unless($etat->statut === 'archive', 422, 'Cet état n’est pas archivé.');
        $etat->update(['statut' => 'valide', 'archive_par' => null, 'archive_at' => null]);
        return response()->json(['success' => true, 'data' => $etat->fresh()]);
    }

    public function transmettreSica(Request $request, etat_paie_indemnites $etat)
    {
        $this->admin($request);
        abort_unless(in_array($etat->statut, ['valide', 'archive'], true) && $etat->verrouille, 422, 'L’état doit être validé et verrouillé avant transmission à la SICA.');
        $etat->update(['transmit_sica' => true]);
        return response()->json(['success' => true, 'message' => 'État transmis à la SICA.', 'data' => $etat->fresh()]);
    }

    private function creerEtat(Request $request, string $type, string $debut, string $fin, array $elements, ?int $beneficiaireId, array $perimetre, ?string $centre = null, ?string $session = null): etat_paie_indemnites
    {
        return etat_paie_indemnites::create(['reference' => 'EPA-'.now()->format('Ymd').'-'.strtoupper(Str::random(6)), 'type' => $type, 'beneficiaire_id' => $beneficiaireId, 'utilisateur_id' => $request->user()->id, 'date_generation' => today(), 'periode_debut' => $debut, 'periode_fin' => $fin, 'lieu_examen' => $centre, 'session' => $session, 'perimetre' => $perimetre, 'details' => ['elements' => $elements, 'nombre_elements' => count($elements)], 'total_montant' => collect($elements)->sum('montant')]);
    }

    private function elements(string $debut, string $fin, ?array $beneficiaires): array
    {
        $indemnites = indemnites::query()->where('statut', 'valide')->whereBetween('valide_at', [$debut.' 00:00:00', $fin.' 23:59:59'])->when($beneficiaires, fn ($q) => $q->whereIn('utilisateur_id', $beneficiaires))->get()->map(fn ($i) => ['source' => 'indemnite', 'id' => $i->id, 'beneficiaire_id' => $i->utilisateur_id, 'date' => $i->valide_at?->toDateString(), 'libelle' => 'Indemnité #'.$i->id, 'montant' => (float) $i->montant_total]);
        $missions = MissionDeplacement::query()->whereIn('statut', ['remboursement_en_attente', 'remboursee'])->whereBetween('valide_at', [$debut.' 00:00:00', $fin.' 23:59:59'])->when($beneficiaires, fn ($q) => $q->whereIn('beneficiaire_id', $beneficiaires))->get()->map(fn ($m) => ['source' => 'frais_deplacement', 'id' => $m->id, 'beneficiaire_id' => $m->beneficiaire_id, 'date' => $m->valide_at?->toDateString(), 'libelle' => 'Mission '.$m->reference, 'montant' => (float) $m->montant_approuve]);
        $noms = utilisateurs::whereIn('id', $indemnites->pluck('beneficiaire_id')->merge($missions->pluck('beneficiaire_id'))->unique())->get()->keyBy('id');
        return $indemnites->merge($missions)->map(function (array $element) use ($noms) { $u = $noms->get($element['beneficiaire_id']); $element['beneficiaire'] = $u ? trim($u->prenom.' '.$u->nom) : 'Bénéficiaire supprimé'; return $element; })->values()->all();
    }

    private function isAdmin(Request $request): bool { return strtolower((string) $request->user()->loadMissing('role')->role?->libelle) === 'administrateur'; }
    private function admin(Request $request): void { abort_unless($this->isAdmin($request), 403, 'Réservé aux administrateurs.'); }
    private function perPage(Request $request): int { return min(max($request->integer('per_page', 15), 1), 100); }
}
