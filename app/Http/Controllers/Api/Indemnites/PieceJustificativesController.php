<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StorePieceJustificativeRequest;
use App\Http\Requests\Indemnites\UpdatePieceJustificativeRequest;
use App\Http\Requests\Indemnites\DeposerPieceJustificativeRequest;
use App\Http\Requests\Indemnites\DeposerLotPiecesJustificativesRequest;
use App\Http\Requests\Indemnites\ClassifierPieceJustificativeRequest;
use App\Http\Requests\Indemnites\VerifierPieceJustificativeRequest;
use App\Http\Requests\Indemnites\RejeterPieceJustificativeRequest;
use App\Models\Indemnite\piece_justificatives;
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

    /**
     * Depot groupe des pieces d'UN membre depuis la modale "Ajouter une
     * piece" (page Pieces justificatives, un bouton par membre) : 5
     * fichiers manuels obligatoires (service_fait/ordre_mission/
     * rapport_mission/bulletin_salaire/accuse_reception — voir
     * DeposerLotPiecesJustificativesRequest), plus le "dossier_convocation"
     * qui est toujours rattache automatiquement (jamais televerse) — voir
     * attacherDossierConvocation(). Total attendu : 6 documents par membre.
     */
    public function deposerLot(DeposerLotPiecesJustificativesRequest $request)
    {
        $convocationId = (int) $request->validated('convocation_id');
        $enseignantId = (int) $request->validated('enseignant_id');
        $centreId = $request->validated('centre_id');
        $depositaireId = $request->user()?->id;

        $piecesCreees = [];

        foreach (array_keys(piece_justificatives::TYPES) as $type) {
            if ($type === 'dossier_convocation' || ! $request->hasFile($type)) {
                continue;
            }

            $fichier = $request->file($type);

            $piecesCreees[] = piece_justificatives::create([
                'type' => $type,
                'convocation_id' => $convocationId,
                'enseignant_id' => $enseignantId,
                'centre_id' => $centreId,
                'depositaire_id' => $depositaireId,
                'statut' => 'depose',
                'date_depot' => now()->toDateString(),
                'chemin' => $fichier->store('pieces-justificatives', 'public'),
                'nom_original' => $fichier->getClientOriginalName(),
                'mime_type' => $fichier->getClientMimeType(),
                'taille' => $fichier->getSize(),
            ]);
        }

        $dossierConvocation = $this->attacherDossierConvocation($convocationId, $enseignantId, $centreId, $depositaireId);

        if ($dossierConvocation) {
            $piecesCreees[] = $dossierConvocation;
        }

        if (empty($piecesCreees)) {
            // Rien de NOUVEAU cette fois (aucun fichier fourni) : si le
            // dossier de convocation etait deja rattache lors d'un depot
            // precedent, ce n'est pas une erreur — seulement s'il n'y a
            // litteralement aucun document pour ce membre.
            $dossierDejaPresent = piece_justificatives::where('convocation_id', $convocationId)
                ->where('enseignant_id', $enseignantId)
                ->where('type', 'dossier_convocation')
                ->exists();

            if (! $dossierDejaPresent) {
                return $this->error("Aucun document reçu et le dossier de convocation n'a pas pu être rattaché (PDF indisponible).", 422);
            }

            return $this->success('Aucun nouveau document à déposer (le dossier de convocation est déjà rattaché).', []);
        }

        return $this->success('Pièces justificatives déposées avec succès.', $piecesCreees, 201);
    }

    /**
     * Rattache le "dossier_convocation" (PDF de la convocation, deja
     * genere/telechargeable via ConvocationPdfController) au dossier du
     * membre — jamais televerse manuellement, contrairement aux 4 autres
     * types. Idempotent : ne cree rien si ce membre a deja ce document pour
     * cette convocation (evite d'empiler la meme reference a chaque
     * soumission de la modale).
     */
    private function attacherDossierConvocation(int $convocationId, int $enseignantId, ?int $centreId, ?int $depositaireId): ?piece_justificatives
    {
        $existe = piece_justificatives::where('convocation_id', $convocationId)
            ->where('enseignant_id', $enseignantId)
            ->where('type', 'dossier_convocation')
            ->exists();

        if ($existe) {
            return null;
        }

        $chemin = "convocations/{$convocationId}/convocation-{$convocationId}.pdf";

        if (! Storage::disk('public')->exists($chemin)) {
            $resultat = app(ConvocationPdfController::class)->generer((string) $convocationId);

            if ($resultat->getData()->success !== true) {
                return null;
            }
        }

        return piece_justificatives::create([
            'type' => 'dossier_convocation',
            'convocation_id' => $convocationId,
            'enseignant_id' => $enseignantId,
            'centre_id' => $centreId,
            'depositaire_id' => $depositaireId,
            'statut' => 'depose',
            'date_depot' => now()->toDateString(),
            'chemin' => $chemin,
            'nom_original' => "convocation-{$convocationId}.pdf",
            'mime_type' => 'application/pdf',
            'taille' => Storage::disk('public')->size($chemin),
        ]);
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

    /**
     * Affiche le document dans le navigateur (Content-Disposition: inline)
     * plutot que de forcer un telechargement immediat — l'utilisateur peut
     * ensuite le telecharger lui-meme depuis la visionneuse du navigateur
     * s'il le souhaite (voir demande utilisateur).
     */
    public function download(string $id)
    {
        $piece = piece_justificatives::find($id);

        if (! $piece) {
            return $this->error('Pièce justificative introuvable.', 404);
        }

        if (! $piece->chemin || ! Storage::disk('public')->exists($piece->chemin)) {
            return $this->error('Fichier introuvable sur le serveur.', 404);
        }

        return Storage::disk('public')->response($piece->chemin, $piece->nom_original);
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
