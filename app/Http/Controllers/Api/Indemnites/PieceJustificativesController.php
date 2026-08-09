<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StorePieceJustificativeRequest;
use App\Http\Requests\Indemnites\UpdatePieceJustificativeRequest;
use App\Http\Requests\Indemnites\DeposerPieceJustificativeRequest;
use App\Http\Requests\Indemnites\ClassifierPieceJustificativeRequest;
use App\Http\Requests\Indemnites\VerifierPieceJustificativeRequest;
use App\Http\Requests\Indemnites\RejeterPieceJustificativeRequest;
use App\Models\piece_justificatives;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PieceJustificativesController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = piece_justificatives::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('convocation_id')) {
            $query->where('convocation_id', $request->query('convocation_id'));
        }

        $pieces = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des pièces justificatives.', $pieces);
    }

    public function store(StorePieceJustificativeRequest $request)
    {
        $piece = $this->enregistrerPiece($request);

        return $this->success('Pièce justificative créée avec succès.', $piece, 201);
    }

    public function show(string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        return $this->success('Pièce justificative trouvée.', $piece);
    }

    public function update(UpdatePieceJustificativeRequest $request, string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        $piece->update($request->validated());

        return $this->success('Pièce justificative mise à jour avec succès.', $piece);
    }

    public function destroy(string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        if ($piece->chemin) {
            Storage::disk('public')->delete($piece->chemin);
        }

        $piece->delete();

        return $this->success('Pièce justificative supprimée avec succès.');
    }

    /**
     * Dépôt dédié d'une pièce justificative avec fichier obligatoire.
     */
    public function deposer(DeposerPieceJustificativeRequest $request)
    {
        $piece = $this->enregistrerPiece($request);

        return $this->success('Pièce justificative déposée avec succès.', $piece, 201);
    }

    public function classifier(ClassifierPieceJustificativeRequest $request, string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        $piece->update(['type' => $request->validated('type')]);

        return $this->success('Pièce justificative classifiée avec succès.', $piece);
    }

    public function verifier(VerifierPieceJustificativeRequest $request, string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        $piece->update([
            'conforme' => $request->validated('conforme'),
            'commentaire_verification' => $request->validated('commentaire_verification'),
            'verifie_par' => $request->user()?->id,
            'verifie_at' => now(),
        ]);

        return $this->success('Vérification enregistrée avec succès.', $piece);
    }

    public function valider(Request $request, string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        $piece->update([
            'statut' => 'valide',
            'valide_par' => $request->user()?->id,
            'valide_at' => now(),
        ]);

        return $this->success('Pièce justificative validée avec succès.', $piece);
    }

    public function rejeter(RejeterPieceJustificativeRequest $request, string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        $piece->update([
            'statut' => 'rejete',
            'commentaire_rejet' => $request->validated('commentaire_rejet'),
        ]);

        return $this->success('Pièce justificative rejetée.', $piece);
    }

    public function download(string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        if (! $piece->chemin || ! Storage::disk('public')->exists($piece->chemin)) {
            return $this->error('Fichier introuvable sur le serveur.', 404);
        }

        return Storage::disk('public')->download($piece->chemin, $piece->nom_original);
    }

    /**
     * Notifie le déposant du statut de sa pièce justificative.
     *
     * NOTE: aucun système de notification dédié (mail/DB) n'est encore
     * branché pour ce module ; l'horodatage et le message sont donc tracés
     * directement sur la pièce, en attendant l'intégration éventuelle avec
     * App\Models\admin\Notification.
     */
    public function notifier(Request $request, string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        $piece->update([
            'notification_at' => now(),
            'notification_message' => $request->input('message', 'Mise à jour du statut de votre pièce justificative.'),
        ]);

        return $this->success('Notification envoyée avec succès.', $piece);
    }

    private function enregistrerPiece(Request $request): piece_justificatives
    {
        $data = $request->validated();

        if ($request->hasFile('document')) {
            $fichier = $request->file('document');
            $data['chemin'] = $fichier->store('pieces-justificatives', 'public');
            $data['nom_original'] = $fichier->getClientOriginalName();
            $data['mime_type'] = $fichier->getClientMimeType();
            $data['taille'] = $fichier->getSize();
            unset($data['document']);
        }

        $data['depositaire_id'] = $data['depositaire_id'] ?? $request->user()?->id;
        $data['statut'] = $data['statut'] ?? 'depose';
        $data['date_depot'] = $data['date_depot'] ?? now()->toDateString();

        return piece_justificatives::create($data);
    }
}
