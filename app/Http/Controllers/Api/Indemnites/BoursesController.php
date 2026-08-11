<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\StoreBourseRequest;
use App\Http\Requests\Indemnites\UpdateBourseRequest;
use App\Http\Requests\Indemnites\RejeterBourseRequest;
use App\Http\Requests\Indemnites\DeposerPieceBourseRequest;
use App\Models\AttributionBourse;
use App\Models\TypeBourse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BoursesController extends Controller
{
    use ApiResponseTrait;

    public function index(Request $request)
    {
        $query = AttributionBourse::query();

        if ($request->filled('statut')) {
            $query->where('statut', $request->query('statut'));
        }

        if ($request->filled('etudiant_id')) {
            $query->where('etudiant_id', $request->query('etudiant_id'));
        }

        $bourses = $query->latest()->paginate($request->integer('per_page', 15));

        return $this->success('Liste des dossiers de bourses.', $bourses);
    }

    public function store(StoreBourseRequest $request)
    {
        $data = $request->validated();
        $data['statut'] = 'en_attente';

        if (empty($data['montant_mensuel'])) {
            $type = TypeBourse::find($data['type_bourse_id']);
            $data['montant_mensuel'] = $type->montant_mensuel ?? 0;
        }

        $bourse = AttributionBourse::create($data);

        return $this->success('Demande de bourse créée avec succès.', $bourse, 201);
    }

    public function show(string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        return $this->success('Dossier de bourse trouvé.', $bourse);
    }

    public function update(UpdateBourseRequest $request, string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $bourse->update($request->validated());

        return $this->success('Dossier de bourse mis à jour avec succès.', $bourse);
    }

    public function destroy(string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $bourse->delete();

        return $this->success('Dossier de bourse supprimé avec succès.');
    }

    /**
     * Valide et attribue la bourse (le statut "valide" fait à la fois office
     * de validation du dossier et d'attribution, en l'absence d'un statut distinct dans le schéma actuel).
     */
    public function valider(string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $bourse->update(['statut' => 'valide']);

        return $this->success('Bourse validée et attribuée avec succès.', $bourse);
    }

    public function rejeter(RejeterBourseRequest $request, string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $bourse->update([
            'statut' => 'rejete',
            'commentaire' => $request->validated('commentaire'),
        ]);

        return $this->success('Dossier de bourse rejeté.', $bourse);
    }

    public function calculerMontant(string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $type = TypeBourse::find($bourse->type_bourse_id);
        $montantMensuel = $type->montant_mensuel ?? 0;
        $dureeMois = $type->duree_mois ?? 1;

        $bourse->update(['montant_mensuel' => $montantMensuel]);

        return $this->success('Montant de la bourse calculé avec succès.', [
            'bourse_id' => $bourse->id,
            'montant_mensuel' => $montantMensuel,
            'duree_mois' => $dureeMois,
            'montant_total_estime' => $montantMensuel * $dureeMois,
        ]);
    }

    /**
     * Liste les pièces déposées pour un dossier de bourse.
     *
     * NOTE: il n'existe pas encore de table/modèle dédié aux pièces des
     * bourses. En attendant une migration `pieces_bourses`, les fichiers
     * sont simplement stockés sur le disque `public` sous
     * `bourses/{id}/` et listés directement depuis le disque.
     */
    public function pieces(string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $fichiers = collect(Storage::disk('public')->files("bourses/{$id}"))
            ->map(fn ($chemin) => [
                'chemin' => $chemin,
                'url' => Storage::disk('public')->url($chemin),
            ])->values();

        return $this->success('Pièces du dossier de bourse.', $fichiers);
    }

    public function deposerPiece(DeposerPieceBourseRequest $request, string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $fichier = $request->file('document');
        $chemin = $fichier->store("bourses/{$id}", 'public');

        return $this->success('Pièce déposée avec succès.', [
            'type' => $request->validated('type'),
            'chemin' => $chemin,
            'url' => Storage::disk('public')->url($chemin),
        ], 201);
    }

    public function archiver(string $id)
    {
        $bourse = AttributionBourse::find($id);

        if (! $bourse) {
            return $this->error('Dossier de bourse introuvable.', 404);
        }

        $bourse->update(['statut' => 'archive']);

        return $this->success('Dossier de bourse archivé avec succès.', $bourse);
    }
}
