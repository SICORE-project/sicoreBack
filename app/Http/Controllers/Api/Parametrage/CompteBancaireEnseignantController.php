<?php

namespace App\Http\Controllers\Api\Parametrage;

use App\Http\Controllers\Controller;
use App\Http\Requests\Parametrage\StoreCompteBancaireEnseignantRequest;
use App\Http\Resources\Parametrage\CompteBancaireEnseignantResource;
use App\Models\Parametrage\CompteBancaireEnseignant;
use App\Models\Parametrage\Enseignant;

class CompteBancaireEnseignantController extends Controller
{
    public function store(StoreCompteBancaireEnseignantRequest $request, Enseignant $enseignant)
    {
        $compte = CompteBancaireEnseignant::create([
            ...$request->validated(),
            'enseignant_id' => $enseignant->id,
        ]);

        return (new CompteBancaireEnseignantResource($compte->load('institutionFinanciere')))
            ->additional([
                'success' => true,
                'message' => 'Banque associée à l’enseignant avec succès.',
            ])
            ->response()
            ->setStatusCode(201);
    }
}
