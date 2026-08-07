<?php

namespace App\Http\Controllers;

use App\Models\AccuseReception;
use App\Models\ModeleAccuseReception;
use App\Models\PolitiqueArchivageAccuse;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AccuseReceptionController extends Controller
{
    public function index(Request $request) { $query = AccuseReception::with(['beneficiaire', 'modele', 'convocation']); if (! $this->isAdmin($request)) $query->where('beneficiaire_id', $request->user()->id); foreach (['statut', 'beneficiaire_id', 'session'] as $f) if ($request->filled($f)) $query->where($f, $request->input($f)); if ($request->filled('date_debut')) $query->whereDate('recu_at', '>=', $request->input('date_debut')); if ($request->filled('date_fin')) $query->whereDate('recu_at', '<=', $request->input('date_fin')); if ($request->filled('recherche')) $query->where(fn ($q) => $q->where('reference', 'like', '%'.$request->input('recherche').'%')->orWhere('objet', 'like', '%'.$request->input('recherche').'%')); return response()->json(['success' => true, 'data' => $query->latest('recu_at')->paginate($this->perPage($request))]); }
    public function show(Request $request, AccuseReception $accuse) { $this->access($request, $accuse); return response()->json(['success' => true, 'data' => $accuse->load(['beneficiaire', 'modele', 'convocation'])]); }
    public function modeles(Request $request) { $this->admin($request); return response()->json(['success' => true, 'data' => ModeleAccuseReception::latest()->paginate($this->perPage($request))]); }
    public function creerModele(Request $request) { $this->admin($request); $data = $request->validate(['nom' => ['required', 'string', 'max:255'], 'objet' => ['required', 'string', 'max:255'], 'contenu' => ['required', 'string', 'max:10000'], 'actif' => ['nullable', 'boolean']]); return response()->json(['success' => true, 'data' => ModeleAccuseReception::create($data + ['cree_par' => $request->user()->id])], 201); }
    public function modifierModele(Request $request, ModeleAccuseReception $modele) { $this->admin($request); $modele->update($request->validate(['nom' => ['sometimes', 'string', 'max:255'], 'objet' => ['sometimes', 'string', 'max:255'], 'contenu' => ['sometimes', 'string', 'max:10000'], 'actif' => ['nullable', 'boolean']])); return response()->json(['success' => true, 'data' => $modele->fresh()]); }
    public function politique(Request $request) { $this->admin($request); return response()->json(['success' => true, 'data' => PolitiqueArchivageAccuse::latest()->first()]); }
    public function enregistrerPolitique(Request $request) { $this->admin($request); $data = $request->validate(['duree_conservation_annees' => ['required', 'integer', 'min:1', 'max:100'], 'acces_admin_seul' => ['required', 'boolean']]); $politique = PolitiqueArchivageAccuse::latest()->first() ?? new PolitiqueArchivageAccuse(); $politique->fill($data + ['modifie_par' => $request->user()->id])->save(); return response()->json(['success' => true, 'data' => $politique]); }
    public function signer(Request $request, AccuseReception $accuse) { $this->access($request, $accuse); abort_unless($accuse->statut === 'genere', 422, 'Cet accusé a déjà été signé ou archivé.'); $data = $request->validate(['type_signature' => ['required', 'in:electronique,manuelle'], 'signataire_nom' => ['required', 'string', 'max:255'], 'signature' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]); $path = $request->file('signature')?->store("accuses-reception/{$accuse->id}", 'local'); $accuse->update($data + ['signature_chemin' => $path, 'statut' => 'signe', 'signe_at' => now()]); return response()->json(['success' => true, 'data' => $accuse->fresh()]); }
    public function archiver(Request $request, AccuseReception $accuse) { $this->admin($request); abort_unless($accuse->statut === 'signe', 422, 'Seul un accusé signé peut être archivé.'); $accuse->update(['statut' => 'archive', 'archive_par' => $request->user()->id, 'archive_at' => now()]); return response()->json(['success' => true, 'data' => $accuse->fresh()]); }
    public function exporter(Request $request, AccuseReception $accuse) { $this->access($request, $accuse); $accuse->load('beneficiaire'); return Pdf::loadView('accuses-reception.document', compact('accuse'))->download("{$accuse->reference}.pdf"); }
    public function signature(Request $request, AccuseReception $accuse) { $this->access($request, $accuse); abort_unless($accuse->signature_chemin && Storage::disk('local')->exists($accuse->signature_chemin), 404, 'Signature manuscrite ou électronique non jointe.'); return Storage::disk('local')->download($accuse->signature_chemin); }
    private function isAdmin(Request $request): bool { return strtolower((string) $request->user()->loadMissing('role')->role?->libelle) === 'administrateur'; }
    private function admin(Request $request): void { abort_unless($this->isAdmin($request), 403, 'Réservé aux administrateurs.'); }
    private function access(Request $request, AccuseReception $accuse): void {
        $admin = $this->isAdmin($request);
        $adminOnly = (bool) (PolitiqueArchivageAccuse::latest()->value('acces_admin_seul') ?? true);
        abort_unless($admin || (! ($accuse->statut === 'archive' && $adminOnly) && $accuse->beneficiaire_id === $request->user()->id), 403, 'Accès non autorisé.');
    }
    private function perPage(Request $request): int { return min(max($request->integer('per_page', 15), 1), 100); }
}
