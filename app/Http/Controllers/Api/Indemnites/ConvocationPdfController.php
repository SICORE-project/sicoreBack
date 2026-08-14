<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Indemnite\Convocations as ConvocationModel;
use Illuminate\Support\Facades\Storage;

class ConvocationPdfController extends Controller
{
    use ApiResponseTrait;

    /**
     * Relations nécessaires aux blades pdf/convocation.blade.php et ses
     * partials (_jury_examen, _generique). Centralisé ici pour ne pas
     * désynchroniser generer() et un futur aperçu/preview() qui utiliserait
     * la même vue.
     */
    private const RELATIONS_POUR_PDF = [
        'typeConvocation',
        'enseignants.lieuService',
        'centres.chefCentre',
        'centres.presidentJury',
        'centres.metiers.enseignants.lieuService',
    ];

    /**
     * Génère le PDF de la convocation et le stocke sur le disque `public`.
     *
     * NOTE: nécessite le package barryvdh/laravel-dompdf, ajouté à
     * composer.json (voir README-corrections.md). Le code reste défensif
     * (class_exists) au cas où le package ne serait pas encore installé
     * sur l'environnement courant.
     */
    public function generer(string $id)
    {
        $convocation = ConvocationModel::with(self::RELATIONS_POUR_PDF)->find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return $this->error(
                "La génération de PDF nécessite le package 'barryvdh/laravel-dompdf' (composer require barryvdh/laravel-dompdf).",
                501
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.convocation', [
            'convocation' => $convocation,
        ]);

        $chemin = "convocations/{$convocation->id}/convocation-{$convocation->id}.pdf";
        Storage::disk('public')->put($chemin, $pdf->output());

        $convocation->update(['fichier_chemin' => $chemin]);

        return $this->success('PDF généré avec succès.', [
            'convocation_id' => $convocation->id,
            'chemin' => $chemin,
            'url' => Storage::disk('public')->url($chemin),
        ]);
    }

    /**
     * Télécharge le PDF de la convocation (le génère au préalable si besoin).
     */
    public function download(string $id)
    {
        $convocation = ConvocationModel::find($id);

        if (! $convocation) {
            return $this->error('Convocation introuvable.', 404);
        }

        $chemin = "convocations/{$convocation->id}/convocation-{$convocation->id}.pdf";

        if (! Storage::disk('public')->exists($chemin)) {
            $resultat = $this->generer($id);

            if ($resultat->getData()->success !== true) {
                return $resultat;
            }
        }

        return Storage::disk('public')->download($chemin, "convocation-{$convocation->id}.pdf");
    }
}
