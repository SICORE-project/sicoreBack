<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\Indemnites\Concerns\ApiResponseTrait;
use App\Models\Indemnite\MissionDeplacement;
use Illuminate\Support\Facades\Storage;



class FraisDeplacementPdfController extends Controller
{
    use ApiResponseTrait;

   
    private const RELATIONS_POUR_PDF = [
        'beneficiaire',
        'convocation',
        'justificatifs',
    ];

    /**
     * Génère le PDF de la fiche et le stocke sur le disque `public`.
     *
     * NOTE: nécessite le package barryvdh/laravel-dompdf, ajouté à
     * composer.json (voir README-corrections.md).
     */
    public function generer(string $id)
    {
        $mission = MissionDeplacement::with(self::RELATIONS_POUR_PDF)->find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        if (! class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            return $this->error(
                "La génération de PDF nécessite le package 'barryvdh/laravel-dompdf' (composer require barryvdh/laravel-dompdf).",
                501
            );
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.frais-deplacement', [
            'mission' => $mission,
        ]);

        $chemin = "frais-deplacement/{$mission->id}/fiche-{$mission->id}.pdf";
        Storage::disk('public')->put($chemin, $pdf->output());

        return $this->success('PDF généré avec succès.', [
            'mission_id' => $mission->id,
            'chemin' => $chemin,
            'url' => Storage::disk('public')->url($chemin),
        ]);
    }

    /**
     * Télécharge le PDF de la fiche — régénéré à CHAQUE téléchargement
     * (contrairement à ConvocationPdfController::download(), qui réutilise
     * le fichier déjà généré) : la fiche peut être modifiée après coup
     * (voir update()), un PDF mis en cache aurait pu rester périmé et
     * afficher d'anciennes données (bug constaté : "le PDF téléchargé
     * affiche toujours..." après un changement de la vue pdf).
     */
    public function download(string $id)
    {
        $mission = MissionDeplacement::find($id);

        if (! $mission) {
            return $this->error('Fiche de déplacement introuvable.', 404);
        }

        $resultat = $this->generer($id);

        if ($resultat->getData()->success !== true) {
            return $resultat;
        }

        $chemin = "frais-deplacement/{$mission->id}/fiche-{$mission->id}.pdf";

        return Storage::disk('public')->download($chemin, "fiche-deplacement-{$mission->id}.pdf");
    }
}
