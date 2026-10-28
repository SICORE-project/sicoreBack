<?php

namespace App\Services\Import;

use App\Services\Parametrage\RegionalStructureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Import CSV des données de base (voir config/imports.php).
 *
 * Import partiel : les lignes invalides sont rapportées ligne par ligne et n'empêchent pas
 * l'écriture des lignes valides. Une ligne dont la clé existe déjà est IGNORÉE (comptée dans
 * « ignores ») : elle n'écrase jamais l'existant et n'est pas considérée comme une erreur.
 */
class ReferentielImportService
{
    /** @return array<string, array{libelle: string, colonnes: array}> */
    public function entites(): array
    {
        return array_map(fn (array $definition) => $this->ajuster($definition), config('imports'));
    }

    public function definition(string $entite): array
    {
        $definition = config("imports.$entite");

        if (! $definition) {
            throw new InvalidArgumentException("Référentiel inconnu : $entite.");
        }

        return $this->ajuster($definition);
    }

    /** Longueur maximale réellement acceptée par la colonne de la base (si plus petite que celle de la configuration). */
    private function ajuster(array $definition): array
    {
        if (! Schema::hasTable($definition['table'])) {
            return $definition;
        }

        $longueurs = [];
        foreach (DB::select('select COLUMN_NAME as nom, CHARACTER_MAXIMUM_LENGTH as longueur from information_schema.columns where table_schema = database() and table_name = ?', [$definition['table']]) as $c) {
            $longueurs[$c->nom] = $c->longueur ? (int) $c->longueur : null;
        }

        foreach ($definition['colonnes'] as $nom => $regle) {
            $limite = $longueurs[$regle['cible'] ?? $nom] ?? null;
            if ($limite && (! isset($regle['max']) || $limite < $regle['max'])) {
                $definition['colonnes'][$nom]['max'] = $limite;
            }
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
     * @return array{
     *     total: int,
     *     crees: int,
     *     ignores: int,
     *     erreurs: array<int, array{ligne: int, message: string}>,
     *     importe: bool,
     *     statut: 'ok'|'partiel'|'echec'
     * }
     */
    public function traiter(string $entite, string $chemin, bool $ecrire): array
    {
        $definition = $this->definition($entite);
        $this->verifierTable($definition);

        $requises = array_keys(array_filter($definition['colonnes'], fn (array $c) => ! empty($c['requis'])));
        $lignes = $this->lireCsv($chemin, $requises);

        $rapport = [
            'total'   => count($lignes),
            'crees'   => 0,
            'ignores' => 0,
            'erreurs' => [],
            'importe' => false,
        ];

        $operations = [];
        $cles = [];
        $clesEnBase = []; // clés déjà présentes en base, préchargées par lot

        // Préchargement des clés existantes pour éviter une requête par ligne.
        $toutesLesCles = [];
        foreach ($lignes as $ligne) {
            if (($cle = trim((string) ($ligne[$definition['cle']] ?? ''))) !== '') {
                $toutesLesCles[] = $cle;
            }
        }
        if ($toutesLesCles) {
            foreach (array_chunk(array_unique($toutesLesCles), 500) as $lot) {
                foreach (DB::table($definition['table'])->whereIn($definition['cle'], $lot)->pluck($definition['cle']) as $cle) {
                    $clesEnBase[mb_strtolower((string) $cle)] = true;
                }
            }
        }

        foreach ($lignes as $numero => $ligne) {
            [$valeurs, $erreurs] = $this->valider($definition, $ligne);

            $cle = $valeurs[$definition['cle']] ?? null;
            if ($cle !== null) {
                $cleNormalisee = mb_strtolower((string) $cle);
                if (isset($cles[$cleNormalisee])) {
                    // Doublon interne au fichier : ignoré (pas une erreur bloquante).
                    $rapport['ignores']++;
                    continue;
                }
                $cles[$cleNormalisee] = true;
            }

            if ($erreurs) {
                $rapport['erreurs'][] = ['ligne' => $numero, 'message' => implode(' ', $erreurs)];
                continue;
            }

            // Doublon déjà présent en base : IGNORÉ , pas d'update.
            if ($cle !== null && isset($clesEnBase[mb_strtolower((string) $cle)])) {
                $rapport['ignores']++;
                continue;
            }

            // Contrôle des colonnes devant rester uniques.
            $collision = false;
            foreach ($definition['uniques'] ?? [] as $colonne) {
                if (isset($valeurs[$colonne]) && $this->dejaPris($definition, $colonne, $valeurs[$colonne], null)) {
                    $rapport['erreurs'][] = ['ligne' => $numero, 'message' => "« $colonne » : « {$valeurs[$colonne]} » existe déjà sur un autre enregistrement."];
                    $collision = true;
                    break;
                }
            }
            if ($collision) {
                continue;
            }

            $rapport['crees']++;
            $operations[] = ['creer', $valeurs];
        }

        if ($ecrire && $operations && $rapport['crees'] > 0) {
            // Transaction par ligne : une erreur inattendue n'annule pas les lignes déjà écrites.
            foreach ($operations as [$action, $valeurs]) {
                DB::transaction(fn () => $this->ecrire($definition, $action, $valeurs, null));
            }
            $rapport['importe'] = true;
        }

        $rapport['statut'] = $this->statut($rapport);

        return $rapport;
    }

    /** @return 'ok'|'partiel'|'echec' */
    private function statut(array $rapport): string
    {
        if ($rapport['total'] === 0) {
            return 'echec';
        }
        if ($rapport['erreurs'] === []) {
            return 'ok';
        }
        // Des erreurs mais au moins une ligne créée/ignorée => partiel.
        if ($rapport['crees'] > 0 || $rapport['ignores'] > 0) {
            return 'partiel';
        }

        return 'echec';
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

        // Excel « Texte Unicode » enregistre en UTF-16 ; CSV UTF-8  ; sinon Windows-1252.
        if (str_starts_with($contenu, "\xFF\xFE")) {
            $contenu = mb_convert_encoding(substr($contenu, 2), 'UTF-8', 'UTF-16LE');
        } elseif (str_starts_with($contenu, "\xFE\xFF")) {
            $contenu = mb_convert_encoding(substr($contenu, 2), 'UTF-8', 'UTF-16BE');
        }
        $contenu = preg_replace('/^\xEF\xBB\xBF/', '', $contenu);

        if (! mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }

        // Fins de ligne : CRLF, LF ou CR seul (anciens exports Excel).
        $contenu = preg_replace('/\r\n|\r/', "\n", $contenu);

        // Séparateur : le plus fréquent dans la première ligne parmi ; , tabulation |
        $premiere = strtok($contenu, "\n") ?: '';
        $separateur = ';';
        $meilleur = -1;
        foreach ([';', ',', "	", '|'] as $candidat) {
            $nombre = substr_count($premiere, $candidat);
            if ($nombre > $meilleur) {
                $meilleur = $nombre;
                $separateur = $candidat;
            }
        }

        $flux = fopen('php://temp', 'r+');
        fwrite($flux, $contenu);
        rewind($flux);

        $entete = fgetcsv($flux, 0, $separateur);
        $entete = array_map(fn ($nom) => mb_strtolower(trim((string) $nom, " \t\n\r\"'")), $entete ?: []);

        $manquantes = array_diff($attendues, $entete);
        if ($manquantes) {
            throw new InvalidArgumentException('Colonne(s) obligatoire(s) manquante(s) dans le fichier : '.implode(', ', $manquantes).'. Colonnes trouvées : '.(implode(', ', array_filter($entete)) ?: 'aucune').'. Utilisez le modèle CSV de ce référentiel.');
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
                    $erreurs[] = "« $nom » : « $brut » n'existe pas — importez d'abord « {$this->libelleDe($regle['reference']['table'])} » (ou corrigez la valeur).";
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

    /** Libellé du référentiel importable qui alimente cette table (pour guider l'ordre d'import). */
    private function libelleDe(string $table): string
    {
        foreach (config('imports') as $definition) {
            if ($definition['table'] === $table) {
                return $definition['libelle'];
            }
        }

        return $table;
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

            if (isset($definition['structure']) && ($entite = $definition['structure']::where($definition['cle'], $valeurs[$definition['cle']])->first())) {
                app(RegionalStructureService::class)->sync($entite);
            }

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