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
                'type' => $this->description($c),
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

    /** `mode=apercu` contrôle le fichier sans rien écrire ; `mode=importer` écrit les lignes valides (doublons ignorés, erreurs signalées ligne par ligne). */
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

        $erreurs = count($rapport['erreurs']);

        $message = match ($rapport['statut']) {
            'ok' => $rapport['importe']
                ? sprintf('Import terminé : %d créé(s), %d ignoré(s) (doublons).', $rapport['crees'], $rapport['ignores'])
                : sprintf('Fichier contrôlé : %d à créer, %d doublon(s) à ignorer.', $rapport['crees'], $rapport['ignores']),
            'partiel' => $rapport['importe']
                ? sprintf('Import partiel : %d créé(s), %d ignoré(s) (doublons), %d erreur(s) ligne(s) non importée(s).', $rapport['crees'], $rapport['ignores'], $erreurs)
                : sprintf('Contrôle terminé : %d à créer, %d doublon(s), %d erreur(s). Corrigez les erreurs pour importer.', $rapport['crees'], $rapport['ignores'], $erreurs),
            default => 'Aucune ligne importée : le fichier contient des erreurs.',
        };

        return response()->json([
            'success' => $rapport['statut'] !== 'echec',
            'message' => $message,
            'data' => $rapport,
        ], $rapport['statut'] === 'echec' ? 422 : 200);
    }

    /** Libellé lisible du format attendu pour une colonne. */
    private function description(array $colonne): string
    {
        if (isset($colonne['reference'])) {
            $cibles = ['regions' => 'une région existante', 'departements' => 'un département existant', 'ias' => 'une IA existante', 'iefs' => 'un IEF existant', 'corps_enseignant' => 'un corps existant', 'categories' => 'une catégorie existante'];
            $par = implode(' ou ', (array) $colonne['reference']['colonne']);
            $texte = ($cibles[$colonne['reference']['table']] ?? 'un enregistrement existant')." ($par)";

            return isset($colonne['reference']['meme']) ? $texte." de l'IA indiquée" : $texte;
        }

        $detail = match ($colonne['type'] ?? 'texte') {
            'entier' => 'nombre entier'.(isset($colonne['min']) ? ' (≥ '.$colonne['min'].')' : ''),
            'decimal' => 'nombre (ex. 1500 ou 1500,50)',
            'date' => 'date (AAAA-MM-JJ ou JJ/MM/AAAA)',
            'email' => 'adresse e-mail',
            'booleen' => 'oui ou non',
            default => isset($colonne['valeurs']) ? implode(' ou ', $colonne['valeurs']) : 'texte',
        };

        if (isset($colonne['max']) && ($colonne['type'] ?? 'texte') !== 'entier') {
            $detail .= ', '.$colonne['max'].' caractères max';
        }

        if (isset($colonne['regex_message'])) {
            $detail .= ' — '.$colonne['regex_message'];
        }

        return $detail;
    }
}