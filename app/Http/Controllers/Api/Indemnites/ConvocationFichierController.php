<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Http\Requests\Indemnites\UploadFichierConvocationRequest;
use App\Models\Indemnite\Convocations as ConvocationModel;
use Illuminate\Support\Facades\Storage;

/**
 * Gère le dépôt du fichier associé à une convocation existante.
 *
 * Auparavant cette action était réalisée en bifurquant dans la méthode
 * store() de ConvocationsController selon la présence d'un {id} de route.
 * Elle est désormais isolée dans son propre controller pour plus de clarté.
 */
class ConvocationFichierController extends Controller
{
    use ApiResponseTrait;

    public function store(UploadFichierConvocationRequest $request, string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        // Supprime l'ancien fichier s'il existe, pour éviter d'accumuler
        // des fichiers orphelins sur le disque à chaque nouveau dépôt.
        if ($convocation->fichier_chemin && Storage::disk('public')->exists($convocation->fichier_chemin)) {
            Storage::disk('public')->delete($convocation->fichier_chemin);
        }

        $chemin = $request->file('fichier')->store("convocations/{$id}", 'public');

        $convocation->update(['fichier_chemin' => $chemin]);

        return $this->success('Fichier déposé avec succès.', [
            'convocation_id' => $convocation->id,
            'chemin' => $chemin,
            'url' => Storage::disk('public')->url($chemin),
        ], 201);
    }
}
