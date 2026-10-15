<?php

namespace App\Services\Import;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Import CSV des données de base (voir config/imports.php).
 *
 * Tout ou rien : si une ligne est invalide, rien n'est écrit. Une ligne dont la clé existe déjà met à jour
 * l'enregistrement (réimport sans doublon).
 */
class ReferentielImportService
{
    /** @return array<string, array{libelle: string, colonnes: array}> */
    public function entites(): array
    {
        return config('imports');
    }

    public function definition(string $entite): array
    {
        $definition = config("imports.$entite");

        if (! $definition) {
            throw new InvalidArgumentException("Référentiel inconnu : $entite.");
        }

        return $definition;
    }

    /** Contenu CSV (séparateur « ; », ouvrable dans Excel) avec une ligne d'exemple. */
    public function modele(string $entite): string
    {
        $colonnes = $this->definition($entite)['colonnes'];
        $sortie = "\xEF\xBB\xBF";
        $sortie .= implode(';', array_keys($colonnes))."\r\n";
        $sortie .= implode(';', array_map(fn (array $c) => $c['exemple'] ?? '', $colonnes))."\r\n";

        return $sortie;
    }

    /**
     * @return array{total: int, crees: int, mis_a_jour: int, inchanges: int, erreurs: array<int, array{ligne: int, message: string}>, importe: bool}
     */
    public function traiter(string $entite, string $chemin, bool $ecrire): array
    {
        $definition = $this->definition($entite);
        $requises = array_keys(array_filter($definition['colonnes'], fn (array $c) => ! empty($c['requis'])));
        $lignes = $this->lireCsv($chemin, $requises);

        $rapport = ['total' => count($lignes), 'crees' => 0, 'mis_a_jour' => 0, 'inchanges' => 0, 'erreurs' => [], 'importe' => false];
        $operations = [];
        $cles = [];

        foreach ($lignes as $numero => $ligne) {
            [$valeurs, $erreurs] = $this->valider($definition, $ligne);

            $cle = $valeurs[$definition['cle']] ?? null;
            if ($cle !== null) {
                if (isset($cles[mb_strtolower((string) $cle)])) {
                    $erreurs[] = "« {$definition['cle']} » en double dans le fichier (ligne {$cles[mb_strtolower((string) $cle)]}).";
                }
                $cles[mb_strtolower((string) $cle)] = $numero;
            }

            if ($erreurs) {
                $rapport['erreurs'][] = ['ligne' => $numero, 'message' => implode(' ', $erreurs)];

                continue;
            }

            $existant = $this->trouver($definition, $valeurs[$definition['cle']]);

            if (! $existant) {
                $rapport['crees']++;
                $operations[] = ['creer', $valeurs, null];
            } elseif ($this->identique($existant, $valeurs)) {
                $rapport['inchanges']++;
            } else {
                $rapport['mis_a_jour']++;
                $operations[] = ['modifier', $valeurs, $existant];
            }
        }

        if ($ecrire && ! $rapport['erreurs']) {
            DB::transaction(function () use ($definition, $operations) {
                foreach ($operations as [$action, $valeurs, $existant]) {
                    $this->ecrire($definition, $action, $valeurs, $existant);
                }
            });
            $rapport['importe'] = true;
        }

        return $rapport;
    }

    /** @return array<int, array<string, string>> lignes indexées par leur numéro dans le fichier */
    private function lireCsv(string $chemin, array $attendues): array
    {
        $contenu = (string) file_get_contents($chemin);
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);

        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }

        $premiere = strtok($contenu, "\n") ?: '';
        $separateur = substr_count($premiere, ';') >= substr_count($premiere, ',') ? ';' : ',';

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, $contenu);
        rewind($flux);

        $entete = fgetcsv($flux, 0, $separateur);
        $entete = array_map(fn ($nom) => mb_strtolower(trim((string) $nom)), $entete ?: []);

        $manquantes = array_diff($attendues, $entete);
        if ($manquantes) {
            throw new InvalidArgumentException('Colonne(s) obligatoire(s) manquante(s) dans le fichier : '.implode(', ', $manquantes).'.');
        }

        $lignes = [];
        $numero = 1;
        while (($champs = fgetcsv($flux, 0, $separateur)) !== false) {
            $numero++;
            if (count(array_filter($champs, fn ($v) => trim((string) $v) !== '')) === 0) {
                continue;
            }
            $ligne = [];
            foreach ($entete as $i => $nom) {
                $ligne[$nom] = trim((string) ($champs[$i] ?? ''));
            }
            $lignes[$numero] = $ligne;
        }
        fclose($flux);

        return $lignes;
    }

    /** @return array{0: array<string, mixed>, 1: array<int, string>} valeurs prêtes pour la table, erreurs */
    private function valider(array $definition, array $ligne): array
    {
        $valeurs = [];
        $erreurs = [];

        foreach ($definition['colonnes'] as $nom => $regle) {
            // Colonne optionnelle absente du fichier : la valeur existante n'est pas touchée.
            if (! array_key_exists($nom, $ligne) && empty($regle['requis'])) {
                continue;
            }
            $brut = $ligne[$nom] ?? '';
            $cible = $regle['cible'] ?? $nom;

            if ($brut === '') {
                if (! empty($regle['requis'])) {
                    $erreurs[] = "« $nom » est obligatoire.";
                } else {
                    $valeurs[$cible] = null;
                }

                continue;
            }

            if (isset($regle['reference'])) {
                $id = DB::table($regle['reference']['table'])->where($regle['reference']['colonne'], $brut)->value('id');
                if (! $id) {
                    $erreurs[] = "« $nom » : « $brut » n'existe pas (importez d'abord ce référentiel).";
                } else {
                    $valeurs[$cible] = $id;
                }

                continue;
            }

            [$valeur, $erreur] = $this->convertir($nom, $brut, $regle);
            if ($erreur) {
                $erreurs[] = $erreur;
            } else {
                $valeurs[$cible] = $valeur;
            }
        }

        return [$valeurs, $erreurs];
    }

    /** @return array{0: mixed, 1: ?string} */
    private function convertir(string $nom, string $brut, array $regle): array
    {
        switch ($regle['type'] ?? 'texte') {
            case 'entier':
                if (! preg_match('/^-?\d+$/', $brut)) {
                    return [null, "« $nom » doit être un nombre entier."];
                }
                $n = (int) $brut;
                if (isset($regle['min']) && $n < $regle['min'] || isset($regle['maxvaleur']) && $n > $regle['maxvaleur']) {
                    return [null, "« $nom » doit être compris entre ".($regle['min'] ?? '…').' et '.($regle['maxvaleur'] ?? '…').'.'];
                }

                return [$n, null];
            case 'decimal':
                $propre = str_replace(',', '.', $brut);

                return is_numeric($propre) ? [(float) $propre, null] : [null, "« $nom » doit être un nombre."];
            case 'date':
                foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
                    $date = Carbon::createFromFormat($format, $brut);
                    if ($date && $date->format($format) === $brut) {
                        return [$date->toDateString(), null];
                    }
                }

                return [null, "« $nom » : date invalide (AAAA-MM-JJ ou JJ/MM/AAAA)."];
            case 'email':
                return filter_var($brut, FILTER_VALIDATE_EMAIL) ? [$brut, null] : [null, "« $nom » : adresse e-mail invalide."];
            default:
                if (isset($regle['valeurs']) && ! in_array(mb_strtolower($brut), $regle['valeurs'], true)) {
                    return [null, "« $nom » doit valoir : ".implode(' ou ', $regle['valeurs']).'.'];
                }
                if (isset($regle['max']) && mb_strlen($brut) > $regle['max']) {
                    return [null, "« $nom » dépasse {$regle['max']} caractères."];
                }

                return [isset($regle['valeurs']) ? mb_strtolower($brut) : $brut, null];
        }
    }

    private function trouver(array $definition, mixed $cle): ?object
    {
        return DB::table($definition['table'])->where($definition['cle'], $cle)->first();
    }

    private function identique(object $existant, array $valeurs): bool
    {
        foreach ($valeurs as $colonne => $valeur) {
            $actuelle = $existant->$colonne ?? null;
            if ($actuelle === null && $valeur === null) {
                continue;
            }
            $memeNombre = is_numeric($actuelle) && is_numeric($valeur) && (float) $actuelle === (float) $valeur;
            if (! $memeNombre && (string) $actuelle !== (string) $valeur) {
                return false;
            }
        }

        return ! (property_exists($existant, 'deleted_at') && $existant->deleted_at !== null);
    }

    private function ecrire(array $definition, string $action, array $valeurs, ?object $existant): void
    {
        $table = $definition['table'];
        $maintenant = now();

        if ($action === 'creer') {
            $donnees = $valeurs + ($definition['defauts'] ?? []);
            if (Schema::hasColumn($table, 'created_at')) {
                $donnees['created_at'] = $maintenant;
            }
            if (Schema::hasColumn($table, 'updated_at')) {
                $donnees['updated_at'] = $maintenant;
            }
            DB::table($table)->insert($donnees);

            return;
        }

        $donnees = $valeurs;
        if (Schema::hasColumn($table, 'updated_at')) {
            $donnees['updated_at'] = $maintenant;
        }
        if (Schema::hasColumn($table, 'deleted_at')) {
            $donnees['deleted_at'] = null;
        }
        DB::table($table)->where($definition['cle'], $existant->{$definition['cle']})->update($donnees);
    }
}
