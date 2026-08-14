<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreAccuseReceptionRequest;
use App\Http\Requests\Indemnites\UpdateAccuseReceptionRequest;
use App\Http\Requests\Indemnites\SignerAccuseReceptionRequest;
use App\Models\AccuseReception;
use App\Models\ModeleAccuseReception;
use App\Models\PolitiqueArchivageAccuse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AccuseReceptionController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = AccuseReception::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('beneficiaire_id')) {
            $query->where('beneficiaire_id', $request->query('beneficiaire_id'));
        }

        $accuses = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des accusés de réception.', $accuses);
    }

    public function store(StoreAccuseReceptionRequest $request)
    {
        $data = $request->validated();
        $data['reference'] = $data['reference'] ?? ('AR-' . Str::upper(Str::random(8)));
        $data['statut'] = $data['statut'] ?? 'en_attente';
        $data['recu_at'] = $data['recu_at'] ?? now();

        $accuse = AccuseReception::create($data);

        return $this->success('Accusé de réception créé avec succès.', $accuse, 201);
    }

    public function show(string $id)
    {
        $accuse = AccuseReception::find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        return $this->success('Accusé de réception trouvé.', $accuse);
    }

    public function update(UpdateAccuseReceptionRequest $request, string $id)
    {
        $accuse = AccuseReception::find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        $accuse->update($request->validated());

        return $this->success('Accusé de réception mis à jour avec succès.', $accuse);
    }

    public function destroy(string $id)
    {
        $accuse = AccuseReception::find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        $accuse->delete();

        return $this->success('Accusé de réception supprimé avec succès.');
    }

   
    public function modeles(Request $request)
    {
        $modeles = ModeleAccuseReception::when(
            $request->filled('actif'),
            fn ($q) => $q->where('actif', $request->boolean('actif'))
        )->orderBy('nom')->get();

        return $this->success('Liste des modèles d\'accusés de réception.', $modeles);
    }

    /**
     * Voir la note de routage sur modeles() : même limitation ici.
     */
    public function politiqueArchivage()
    {
        $politique = PolitiqueArchivageAccuse::first();

        if (! $politique) {
            return $this->error('Aucune politique d\'archivage configurée.', 404);
        }

        return $this->success('Politique d\'archivage des accusés de réception.', $politique);
    }

    public function generer(string $id)
    {
        $accuse = AccuseReception::with('modele')->find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        $contenu = $accuse->contenu ?: ($accuse->modele->contenu ?? null);

        return $this->success('Contenu généré pour l\'accusé de réception.', [
            'accuse_reception_id' => $accuse->id,
            'objet' => $accuse->objet,
            'contenu' => $contenu,
        ]);
    }

    public function signer(SignerAccuseReceptionRequest $request, string $id)
    {
        $accuse = AccuseReception::find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        $data = [
            'type_signature' => $request->validated('type_signature'),
            'signataire_nom' => $request->validated('signataire_nom'),
            'signe_at' => now(),
            'statut' => 'signe',
        ];

        if ($request->hasFile('signature')) {
            $data['signature_chemin'] = $request->file('signature')->store("accuses-reception/{$id}", 'public');
        }

        $accuse->update($data);

        return $this->success('Accusé de réception signé avec succès.', $accuse);
    }

    public function export(string $id)
    {
        $accuse = AccuseReception::with('modele', 'convocation')->find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        return $this->success('Export de l\'accusé de réception.', $accuse);
    }

    public function archiver(Request $request, string $id)
    {
        $accuse = AccuseReception::find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        $politique = PolitiqueArchivageAccuse::first();
        $dureeAnnees = $politique->duree_conservation_annees ?? 5;

        $accuse->update([
            'statut' => 'archive',
            'archive_par' => $request->user()?->id,
            'archive_at' => now(),
            'conserver_jusqu_au' => now()->addYears($dureeAnnees),
        ]);

        return $this->success('Accusé de réception archivé avec succès.', $accuse);
    }

    public function consulter(string $id)
    {
        $accuse = AccuseReception::with('beneficiaire', 'modele', 'convocation')->find($id);

        if (! $accuse) {
            return $this->error('Accusé de réception introuvable.', 404);
        }

        return $this->success('Détail de l\'accusé de réception.', $accuse);
    }
}
