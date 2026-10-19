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
        $this->verifierTable($definition);

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

            $existant = $cle !== null ? $this->trouver($definition, $cle) : null;

            if (! $erreurs) {
                foreach ($definition['uniques'] ?? [] as $colonne) {
                    if (isset($valeurs[$colonne]) && $this->dejaPris($definition, $colonne, $valeurs[$colonne], $existant)) {
                        $erreurs[] = "« $colonne » : « {$valeurs[$colonne]} » existe déjà sur un autre enregistrement.";
                    }
                }
            }

            if ($erreurs) {
                $rapport['erreurs'][] = ['ligne' => $numero, 'message' => implode(' ', $erreurs)];

                continue;
            }

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

    /**
     * La base doit correspondre au schéma attendu : colonnes cibles présentes et aucune colonne
     * obligatoire de la table laissée sans valeur (cas d'une base dont les migrations ne sont pas à jour).
     */
    private function verifierTable(array $definition): void
    {
        $table = $definition['table'];

        if (! Schema::hasTable($table)) {
            throw new InvalidArgumentException("La table « $table » n'existe pas : exécutez les migrations.");
        }

        $fournies = [];
        foreach ($definition['colonnes'] as $nom => $regle) {
            $cible = $regle['cible'] ?? $nom;
            if (! Schema::hasColumn($table, $cible)) {
                throw new InvalidArgumentException("La table « $table » n'a pas la colonne « $cible » : exécutez les migrations.");
            }
            $fournies[] = $cible;
        }

        foreach (array_keys($definition['defauts'] ?? []) as $colonne) {
            $fournies[] = $colonne;
        }
        foreach ($definition['generer'] ?? [] as $colonne) {
            $fournies[] = $colonne;
        }

        $manquantes = [];
        foreach (DB::select('select COLUMN_NAME as nom, IS_NULLABLE as nullable, COLUMN_DEFAULT as defaut, EXTRA as extra, COLUMN_KEY as cle from information_schema.columns where table_schema = database() and table_name = ?', [$table]) as $colonne) {
            $auto = str_contains((string) $colonne->extra, 'auto_increment') || str_contains((string) $colonne->extra, 'GENERATED');
            if ($colonne->nullable === 'NO' && $colonne->defaut === null && ! $auto && ! in_array($colonne->nom, $fournies, true) && ! in_array($colonne->nom, ['created_at', 'updated_at'], true)) {
                $manquantes[] = $colonne->nom;
            }
        }

        if ($manquantes) {
            throw new InvalidArgumentException("La table « $table » exige encore : ".implode(', ', $manquantes).' (base non à jour : exécutez les migrations).');
        }
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
                } elseif (! array_key_exists($cible, $definition['defauts'] ?? [])) {
                    // Cellule vide = valeur effacée, sauf pour une colonne qui a une valeur par défaut (est_actif…).
                    $valeurs[$cible] = null;
                }

                continue;
            }

            if (isset($regle['reference'])) {
                $id = $this->resoudre($regle['reference'], $brut);
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

        foreach ($definition['colonnes'] as $nom => $regle) {
            if (isset($regle['apres'], $valeurs[$nom], $valeurs[$regle['apres']]) && $valeurs[$nom] <= $valeurs[$regle['apres']]) {
                $erreurs[] = "« $nom » doit être postérieure à « {$regle['apres']} ».";
            }
            if (isset($regle['reference']['meme'], $valeurs[$regle['cible'] ?? $nom], $valeurs[$regle['reference']['meme']])) {
                $parent = DB::table($regle['reference']['table'])->where('id', $valeurs[$regle['cible'] ?? $nom])->value($regle['reference']['meme']);
                if ((string) $parent !== (string) $valeurs[$regle['reference']['meme']]) {
                    $erreurs[] = "« $nom » n'appartient pas à l'« {$this->nomDe($definition, $regle['reference']['meme'])} » indiquée.";
                }
            }
        }

        return [$valeurs, $erreurs];
    }

    private function nomDe(array $definition, string $cible): string
    {
        foreach ($definition['colonnes'] as $nom => $regle) {
            if (($regle['cible'] ?? $nom) === $cible) {
                return $nom;
            }
        }

        return $cible;
    }

    /** Identifiant de l'enregistrement référencé, retrouvé par l'une des colonnes indiquées. */
    private function resoudre(array $reference, string $valeur): ?int
    {
        $requete = DB::table($reference['table']);

        if (Schema::hasColumn($reference['table'], 'deleted_at')) {
            $requete->whereNull('deleted_at');
        }

        $requete->where(function ($q) use ($reference, $valeur) {
            foreach ((array) $reference['colonne'] as $colonne) {
                $q->orWhere($colonne, $valeur);
            }
        });

        $id = $requete->value('id');

        return $id ? (int) $id : null;
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
                if ((isset($regle['min']) && $n < $regle['min']) || (isset($regle['maxvaleur']) && $n > $regle['maxvaleur'])) {
                    return [null, isset($regle['min'], $regle['maxvaleur'])
                        ? "« $nom » doit être compris entre {$regle['min']} et {$regle['maxvaleur']}."
                        : "« $nom » doit être ".(isset($regle['min']) ? "supérieur ou égal à {$regle['min']}" : "inférieur ou égal à {$regle['maxvaleur']}").'.'];
                }

                return [$n, null];
            case 'decimal':
                $propre = str_replace([',', ' '], ['.', ''], $brut);
                if (! is_numeric($propre)) {
                    return [null, "« $nom » doit être un nombre."];
                }
                if (isset($regle['min']) && (float) $propre < $regle['min']) {
                    return [null, "« $nom » ne peut pas être inférieur à {$regle['min']}."];
                }

                return [round((float) $propre, 2), null];
            case 'date':
                foreach (['Y-m-d', 'd/m/Y', 'd-m-Y'] as $format) {
                    try {
                        $date = Carbon::createFromFormat($format, $brut);
                    } catch (\Throwable) {
                        continue;
                    }
                    if ($date && $date->format($format) === $brut) {
                        return [$date->toDateString(), null];
                    }
                }

                return [null, "« $nom » : date invalide (AAAA-MM-JJ ou JJ/MM/AAAA)."];
            case 'email':
                if (! filter_var($brut, FILTER_VALIDATE_EMAIL)) {
                    return [null, "« $nom » : adresse e-mail invalide."];
                }
                if (isset($regle['max']) && mb_strlen($brut) > $regle['max']) {
                    return [null, "« $nom » dépasse {$regle['max']} caractères."];
                }

                return [$brut, null];
            case 'booleen':
                $v = mb_strtolower($brut);
                if (in_array($v, ['1', 'oui', 'true', 'actif', 'o'], true)) {
                    return [1, null];
                }
                if (in_array($v, ['0', 'non', 'false', 'inactif', 'n'], true)) {
                    return [0, null];
                }

                return [null, "« $nom » doit valoir oui ou non."];
            default:
                if (isset($regle['valeurs']) && ! in_array(mb_strtolower($brut), $regle['valeurs'], true)) {
                    return [null, "« $nom » doit valoir : ".implode(' ou ', $regle['valeurs']).'.'];
                }
                if (isset($regle['max']) && mb_strlen($brut) > $regle['max']) {
                    return [null, "« $nom » dépasse {$regle['max']} caractères."];
                }
                if (isset($regle['regex']) && ! preg_match($regle['regex'], $brut)) {
                    return [null, "« $nom » : format invalide".(isset($regle['regex_message']) ? " ({$regle['regex_message']})" : '').'.'];
                }

                return [isset($regle['valeurs']) ? mb_strtolower($brut) : $brut, null];
        }
    }

    private function trouver(array $definition, mixed $cle): ?object
    {
        return DB::table($definition['table'])->where($definition['cle'], $cle)->first();
    }

    /** Une autre ligne que celle de la clé a-t-elle déjà cette valeur (colonne devant rester unique) ? */
    private function dejaPris(array $definition, string $colonne, mixed $valeur, ?object $existant): bool
    {
        $requete = DB::table($definition['table'])->where($colonne, $valeur);

        if (Schema::hasColumn($definition['table'], 'deleted_at')) {
            $requete->whereNull('deleted_at');
        }
        if ($existant) {
            $requete->where($definition['cle'], '!=', $existant->{$definition['cle']});
        }

        return $requete->exists();
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
            $donnees = $valeurs;
            foreach ($definition['defauts'] ?? [] as $colonne => $defaut) {
                if (Schema::hasColumn($table, $colonne) && ! array_key_exists($colonne, $donnees)) {
                    $donnees[$colonne] = $defaut;
                }
            }
            foreach ($definition['generer'] ?? [] as $colonne) {
                $donnees[$colonne] = 'ETAB'.strtoupper(bin2hex(random_bytes(8)));
            }
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
