<?php

namespace App\Http\Controllers\Api\Systeme;

use App\Http\Controllers\Controller;
use App\Services\Import\ReferentielImportService;
use Illuminate\Http\Request;
use InvalidArgumentException;

/** Import des données de base réservé au profil Admin System. */
class ImportDonneesController extends Controller
{
    public function __construct(private readonly ReferentielImportService $import) {}

    public function index()
    {
        $donnees = collect($this->import->entites())->map(fn (array $definition, string $cle) => [
            'cle' => $cle,
            'libelle' => $definition['libelle'],
            'cle_unicite' => $definition['cle'],
            'colonnes' => collect($definition['colonnes'])->map(fn (array $c, string $nom) => [
                'nom' => $nom,
                'requis' => (bool) ($c['requis'] ?? false),
                'type' => isset($c['reference']) ? 'référence' : ($c['type'] ?? (isset($c['valeurs']) ? implode('|', $c['valeurs']) : 'texte')),
            ])->values(),
        ])->values();

        return response()->json(['success' => true, 'message' => 'Référentiels importables.', 'data' => $donnees]);
    }

    public function modele(string $entite)
    {
        try {
            $csv = $this->import->modele($entite);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 404);
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"modele-$entite.csv\"",
        ]);
    }

    /** `mode=apercu` contrôle le fichier sans rien écrire ; `mode=importer` écrit si toutes les lignes sont valides. */
    public function traiter(Request $request, string $entite)
    {
        $data = $request->validate([
            'fichier' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'mode' => ['required', 'in:apercu,importer'],
        ], [
            'fichier.mimes' => 'Le fichier doit être au format CSV.',
            'fichier.max' => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        try {
            $rapport = $this->import->traiter($entite, $request->file('fichier')->getRealPath(), $data['mode'] === 'importer');
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        $message = match (true) {
            $rapport['erreurs'] !== [] => 'Le fichier contient des erreurs : rien n\'a été importé.',
            $rapport['importe'] => 'Import terminé.',
            default => 'Fichier contrôlé : aucune erreur.',
        };

        return response()->json(['success' => $rapport['erreurs'] === [], 'message' => $message, 'data' => $rapport], $rapport['erreurs'] === [] ? 200 : 422);
    }
}
