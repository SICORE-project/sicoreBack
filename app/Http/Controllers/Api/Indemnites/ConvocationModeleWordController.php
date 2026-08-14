<?php

namespace App\Http\Controllers\Api\Indemnites;

use App\Http\Controllers\Controller;
use App\Services\Indemnites\ConvocationWordTemplateService;
use Illuminate\Support\Facades\Storage;

/**
 * Modèle Word téléchargeable pour la création d'une convocation (mêmes
 * champs que le formulaire de saisie manuelle), à remplir puis renvoyer
 * via POST convocations/import (cf. ConvocationImportController).
 */
class ConvocationModeleWordController extends Controller
{
    public function __construct(
        private readonly ConvocationWordTemplateService $modeles
    ) {}

    public function telecharger()
    {
        $chemin = $this->modeles->cheminModele();

        return Storage::disk('public')->download($chemin, 'modele-convocation.docx');
    }
}
