# Rapport d'analyse — SICORE Back (Backend de gestion des salaires des corps émergents)

**Date :** 09/07/2026
**Stack :** Laravel 12 · PHP ^8.2 · MySQL (base `sicore`)
**Périmètre analysé :** migrations, modèles, contrôleurs, routes, configuration, tests

---

## 1. Vue d'ensemble

Le projet est un squelette Laravel 12 fraîchement initialisé sur lequel a été posé le **schéma de données** du domaine (24 migrations métier : bulletins, indemnités, convocations, enseignants, IA/IEF/établissements, etc.). À ce stade :

- ✅ Le modèle de données couvre bien le domaine (paie, indemnités d'examen, structures déconcentrées IA/IEF, référentiels).
- ⚠️ **Aucune logique métier n'est encore implémentée** : les 10 contrôleurs sont des squelettes vides, les 23 modèles Eloquent sont vides (pas de `$fillable`, pas de relations, pas de casts).
- ⚠️ **Aucune route API** : `routes/web.php` ne contient que la page d'accueil ; il n'y a ni `routes/api.php`, ni Sanctum/Passport installé.
- 🔴 **Les migrations ne peuvent pas s'exécuter** dans leur ordre actuel (clés étrangères vers des tables créées après — voir §2.1).
- 🔴 **Le projet n'est pas sous contrôle de version Git.**

---

## 2. Constats critiques (bloquants)

### 2.1 Ordre des migrations invalide → `php artisan migrate` échouera

Presque toutes les migrations référencent via `constrained()` des tables **créées plus tard** dans la chronologie. Exemples :

| Migration (heure) | Référence | Table cible créée à |
|---|---|---|
| `indemnites` (12:25) | `utilisateurs`, `type_indemnites` | 12:40 / 12:26 |
| `convocations` (12:30) | `utilisateurs` | 12:40 |
| `detail_bultins` (12:32) | `bultins`, `rubrique_bultins` | 12:38 / 12:33 |
| `bultins` (12:38) | `enseignants`, `ias` | 12:47 / 13:06 |
| `utilisateurs` (12:40) | `enseignants`, `roles` | 12:47 / 12:49 |
| `enseignants` (12:47) | `corps_enseignants`, `specialites`, `diplomes`, `mutuelles`, `institution_financieres` | toutes après |
| `iefs` (13:05) | `ias` | 13:06 |
| `corps_enseignants` (13:07) | `categories` | 13:07:51 |

Sur MySQL/InnoDB, chaque `constrained()` vers une table inexistante lève une erreur immédiate.

**Correctif :** renommer les fichiers de migration pour respecter l'ordre de dépendance (référentiels d'abord : `categories` → `ias` → `iefs` → `etablissements` → `roles`, `mutuelles`, `diplomes`, `specialites`, `institution_financieres`, `corps_enseignants`, `type_indemnites`, `rubrique_bultins` → puis `enseignants` → `utilisateurs` → `convocations`, `bultins`, `indemnites`… → enfin les tables de détail).

### 2.2 Absence de contrôle de version

Le dossier n'est pas un dépôt Git. Pour un projet institutionnel de paie, c'est un risque majeur (perte de travail, pas de traçabilité, pas de revue). → `git init` immédiatement, vérifier que `.env` reste bien dans `.gitignore` et qu'aucun mot de passe n'est documenté en clair.

### 2.3 Configuration base de données non fonctionnelle

`php artisan migrate:status` échoue : `Access denied for user 'root'@'localhost'`. Le mot de passe du `.env` ne correspond pas au MySQL local (XAMPP utilise par défaut `root` sans mot de passe). Les lignes `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` ont aussi des espaces en début de ligne à nettoyer.

---

## 3. Constats majeurs — Modèle de données

### 3.1 Intégrité et traçabilité financière

Pour une application de **paie**, plusieurs choix actuels sont dangereux :

1. **`cascadeOnDelete` sur des données financières.** Supprimer un `utilisateur` supprime ses `indemnites` ; supprimer un `enseignant` supprime ses `bultins` et `delegation_credits`. L'historique de paiement doit être **inaltérable** → utiliser `restrictOnDelete()` + **SoftDeletes** sur les entités (enseignants, utilisateurs) plutôt que la suppression physique.
2. **Aucun snapshot des tarifs.** `indemnites.montant` est calculé à partir de `type_indemnites.prix_unitaire`, mais si le tarif change, on ne peut plus justifier les montants passés. → stocker `prix_unitaire_applique` dans `indemnites`, ou historiser les tarifs (table `tarifs` avec `date_effet`).
3. **Aucune contrainte d'unicité métier :**
   - `bultins` : un enseignant ne doit avoir qu'un bulletin par mois → `unique(['enseignant_id', 'mois_validite'])` ; `numero_ordre` devrait être unique.
   - `bultins.matricule` duplique une donnée qui devrait vivre sur `enseignants` (qui n'a pas de champ matricule !).
4. **Pas de piste d'audit** : qui a généré/validé/transmis un état de paie, quand ? Le booléen `transmit_sica` est insuffisant → prévoir statuts (`brouillon`, `valide`, `transmis`, `paye`) + horodatage + utilisateur, voire un package d'audit (`owen-it/laravel-auditing`).

### 3.2 Relations manquantes ou incohérentes

1. **`enseignants` n'a ni nom, ni prénom, ni matricule, ni contact.** L'identité semble déléguée à `utilisateurs`, mais un enseignant sans compte utilisateur (cas fréquent pour des vacataires) serait alors anonyme. → mettre l'état civil et le matricule sur `enseignants` ; `utilisateurs` ne porte que l'authentification.
2. **Aucun rattachement géographique de l'enseignant** : pas de `etablissement_id` (ni `ief_id`) sur `enseignants`, alors que toute la chaîne IA → IEF → établissement existe. Impossible de produire un état de salaire par IEF/établissement. → ajouter `etablissement_id` sur `enseignants` (ou une table d'affectations historisée `affectations(enseignant_id, etablissement_id, date_debut, date_fin)` — préférable pour les vacataires).
3. **`etat_paie_indemnites` n'est lié à aucune indemnité** : pas de `etat_paie_indemnite_id` dans `indemnites`. Le `total_montant` est donc invérifiable. Idem pour `etat_salaires` qui n'est lié à aucun `bultin`.
4. **`etats_presences` n'a pas de période** (`nombre_jour` seul, sans mois/année) : inutilisable pour calculer un salaire mensuel de contractuel.
5. **Champs doublons assumés** dans `enseignants` : `specialite` (string) + `specialite_id`, `diplome` (string) + `diplome_id` → garder uniquement les FK.
6. **`indemnites` porte des colonnes hétérogènes** (`nombre_copies`, `nombre_heures`, `nombre_kilometrages`, `indice`…) dont une seule est pertinente selon le type. Acceptable en V1, mais valider par type côté application (FormRequest par type d'indemnité).

### 3.3 Doublon de tables utilisateurs

La table Laravel `users` coexiste avec `utilisateurs`. `config/auth.php` pointe sur `App\Models\User`, donc l'authentification n'utilisera pas `utilisateurs`. → choisir **une seule** table : soit adapter `users` (recommandé, tout l'écosystème Laravel en dépend), soit configurer le provider/guard sur `utilisateurs` et faire hériter le modèle de `Authenticatable` avec cast `'password' => 'hashed'`.

### 3.4 Enums en base

`type_indemnites.libelle` est un `enum('correction','surveillance','jury','deplacement')` : ajouter un type demandera une migration `ALTER TABLE`. Puisqu'une table `type_indemnites` existe déjà, le libellé devrait être un simple `string` (ou une FK vers rien — l'enum fait double emploi avec la table elle-même). Même remarque, moindre, pour les statuts (`convocations`, `piece_justificatives`) : utiliser des `string` + Enum PHP 8 (`BackedEnum`) casté dans le modèle.

---

## 4. Constats majeurs — Code applicatif

1. **Conventions de nommage non respectées** : les modèles sont en minuscules/pluriel/snake_case (`bultins`, `etat_salaires`, `piece_justificatives`) au lieu du StudlyCase singulier attendu (`Bulletin`, `EtatSalaire`, `PieceJustificative`). Cela casse les conventions Eloquent (résolution de table, relations devinées, route model binding) et la lisibilité. Orthographe : « bultins » → **bulletins**, `montant_alouer` → `montant_alloue`, `montant_retenus` → `montant_retenues`.
2. **Modèles vides** : aucun `$fillable`/`$guarded`, aucune relation (`hasMany`, `belongsTo`), aucun cast (`date`, `decimal`, `hashed`, enums). Tout est à écrire.
3. **Contrôleurs générés avec `create()`/`edit()`** (orientés vues Blade) alors que le projet est présenté comme un **backend** : préférer `--api` (5 méthodes) + `Route::apiResource()`.
4. **Aucune validation** : prévoir des FormRequests systématiques.
5. **Aucune ressource API** (`JsonResource`) : les réponses exposeraient les modèles bruts (y compris `password` de `utilisateurs` si mal configuré).
6. **Tests inexistants** (seuls les 2 exemples par défaut).

---

## 5. Améliorations techniques recommandées (feuille de route)

### Priorité 0 — Fondations (à faire avant tout code métier)
| # | Action |
|---|---|
| 1 | `git init` + premier commit ; vérifier `.gitignore` (`.env` exclu) |
| 2 | Réordonner les migrations selon les dépendances FK et valider `php artisan migrate:fresh` |
| 3 | Corriger le `.env` (identifiants MySQL réels, supprimer les espaces parasites) |
| 4 | Renommer modèles selon les conventions (`Bulletin`, `Enseignant`, `Indemnite`…) et corriger l'orthographe des tables tant que la base est vide |
| 5 | Trancher `users` vs `utilisateurs` (une seule table d'authentification) |

### Priorité 1 — Socle API
| # | Action |
|---|---|
| 6 | `php artisan install:api` → installe **Laravel Sanctum** + `routes/api.php` ; endpoints d'authentification (login/logout/token) |
| 7 | Rôles et permissions : la table `roles` (un seul champ `libelle`) est trop pauvre → **spatie/laravel-permission** (rôles : gestionnaire IA, gestionnaire IEF, agent SICA, enseignant…) + middleware/Policies par ressource |
| 8 | Compléter les modèles : `$fillable`, relations, casts (`mois_validite` => `date`, `montant` => `decimal:2`, statuts => Enum PHP) |
| 9 | FormRequests de validation pour chaque endpoint d'écriture |
| 10 | `JsonResource` pour chaque modèle exposé + pagination systématique des index |
| 11 | Versionner l'API (`/api/v1/...`) et documenter (Scramble ou L5-Swagger → OpenAPI) |

### Priorité 2 — Robustesse métier (spécifique paie)
| # | Action |
|---|---|
| 12 | Remplacer `cascadeOnDelete` par `restrictOnDelete` sur toutes les FK financières + SoftDeletes sur `enseignants`/`utilisateurs` |
| 13 | Contraintes d'unicité : bulletin unique par (enseignant, mois), `numero_ordre` unique, matricule unique sur `enseignants` |
| 14 | Historiser les tarifs d'indemnités et figer le tarif appliqué dans chaque indemnité |
| 15 | Lier `indemnites` ↔ `etat_paie_indemnites` et `bultins` ↔ `etat_salaires` (FK), et calculer les totaux depuis les lignes |
| 16 | Ajouter la période (mois/année) à `etats_presences` ; ajouter matricule/état civil et affectation (établissement) à `enseignants` |
| 17 | Workflow d'états (brouillon → validé → transmis SICA → payé) avec horodatage et auteur ; audit trail (`owen-it/laravel-auditing`) |
| 18 | Uploads de pièces justificatives via `Storage` (disque privé, URL signées) au lieu d'un simple `document_url` ; valider type MIME et taille |
| 19 | Génération des bulletins/états en **jobs de queue** (la config `QUEUE_CONNECTION=database` est déjà prête) + export PDF (`barryvdh/laravel-dompdf`) |

### Priorité 3 — Qualité et industrialisation
| # | Action |
|---|---|
| 20 | Tests Feature par endpoint (Pest ou PHPUnit) + factories pour chaque modèle ; base de test SQLite en mémoire |
| 21 | Laravel Pint (déjà installé) en pre-commit ; ajouter **Larastan** (analyse statique) |
| 22 | CI (GitHub Actions/GitLab CI) : pint + larastan + tests sur chaque push |
| 23 | Rate limiting sur les routes d'authentification ; `APP_DEBUG=false` et clé régénérée en production ; sauvegardes BD planifiées (`spatie/laravel-backup`) |
| 24 | Rédiger un README projet (installation, conventions, schéma des rôles) à la place du README Laravel par défaut |

---

## 6. Points positifs

- Laravel 12 et PHP 8.2+ : base moderne et supportée.
- Le schéma couvre l'essentiel du domaine, avec des référentiels bien séparés (IA, IEF, établissements, corps, catégories, mutuelles, institutions financières).
- Types monétaires en `decimal(12,2)` (et non `float`) : bon réflexe.
- FK systématiquement déclarées avec `constrained()` : l'intention d'intégrité référentielle est là.
- Scripts composer `setup`/`dev`/`test` bien configurés pour l'équipe.

---

## 7. Conclusion

Le projet est au stade de **squelette avec schéma de données** : rien n'est encore exécutable (migrations en désordre, pas de routes, pas de logique). Avant d'écrire la moindre fonctionnalité, il faut sécuriser les fondations (Git, migrations, conventions de nommage, choix de la table d'authentification). Le modèle de données nécessite une passe de consolidation orientée « données financières » : intégrité (unicité, non-suppression), traçabilité (audit, statuts, tarifs historisés) et rattachement géographique des enseignants, qui est aujourd'hui le manque fonctionnel le plus important pour produire des états de paie par IA/IEF/établissement.
