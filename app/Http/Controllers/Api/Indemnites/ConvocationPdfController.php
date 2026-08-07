<?php

namespace App\Http\Controllers;

use App\Models\Convocations;
use Barryvdh\DomPDF\Facade\Pdf;

class ConvocationPdfController extends Controller
{
    /**
     * Générer et afficher le PDF de la convocation.
     */
    public function generate($convocation)
    {
        // Récupérer la convocation avec ses bénéficiaires
        $convocationModel = Convocations::with('enseignants')
            ->findOrFail($convocation);

        // Générer le PDF à partir de la vue Blade
        $pdf = Pdf::loadView(
            'convocations.pdf',
            [
                'convocation' => $convocationModel,
            ]
        );

        // Afficher le PDF dans le navigateur
        return $pdf->stream(
            'convocation_'.$convocationModel->id.'.pdf'
        );
    }

    /**
     * Générer et télécharger le PDF.
     */
    public function download($convocation)
    {
        // Récupérer la convocation avec ses bénéficiaires
        $convocationModel = Convocations::with('enseignants')
            ->findOrFail($convocation);

        // Générer le PDF
        $pdf = Pdf::loadView(
            'convocations.pdf',
            [
                'convocation' => $convocationModel,
            ]
        );

        // Télécharger le fichier PDF
        return $pdf->download(
            'convocation_'.$convocationModel->id.'.pdf'
        );
    }
}
