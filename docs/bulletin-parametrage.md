# Bulletin : libellés et affichage issus du paramétrage

Périmètre : consultation et impression du bulletin dans Gestion de la paie
(`/api/payroll/payslips/{id}` et vue frontend `pages/paie/payslip`).
Les écrans Administration et Paramétrage, leurs modèles, leurs permissions et
leurs migrations ne sont pas modifiés.

## Contrat entre équipes

La correspondance utilise le code technique exact ou son code officiel défini
dans `config/payroll_bulletin.php` à partir du PDF fourni. Par exemple,
`SALAIRE_BASE` peut reprendre le libellé du CRUD sous le code `SALAIRE_BASE`
ou `10`. Le code technique exact est prioritaire si les deux sont présents.
Aucune correspondance approximative sur les noms n'est effectuée.

Pour la présentation, seuls `id`, `code`, `libelle` et `est_afficher_bulletin`
sont lus. La vue ne recalcule jamais les montants.
Pour le calcul PC/VAC, les quatre retenues IMPR, TRIMF, IPM et CHECKOFF_UES
reprennent désormais leur `montant` mensuel depuis le CRUD des rubriques,
selon leur applicabilité PC/VAC. Voir `paie-retenues-rubriques.md`.
Les autres montants, taux et formules conservent leur fonctionnement existant.
Créer une rubrique quelconque dans le catalogue ne crée pas automatiquement
une nouvelle ligne de paie.
Une rubrique non trouvée utilise le libellé du PDF si elle est connue dans la
table de présentation ; sinon elle conserve le libellé calculé. Elle reste visible.
Le statut actif du catalogue ne supprime pas une ligne déjà calculée.

Les nouveaux calculs enregistrent les réglages dans
`profile_snapshot.bulletin_display`, y compris pour les codes non configurés.
Une modification ou suppression ultérieure du catalogue ne modifie pas leur
affichage. Un recalcul autorisé reprend les réglages du moment.
Aucune migration supplémentaire ni modification des données existantes
n'est nécessaire.

Les bulletins antérieurs, sans cet instantané, utilisent les réglages actuels
à la consultation. Cette lecture n'enregistre rien en base ; le document
mentionne explicitement qu'il utilise les réglages actuels.

La réponse API ajoute `display_settings` (origine, nombre de correspondances,
nombre de lignes masquées) et `rubric_id` sur les lignes affichées.
Les totaux restent ceux du bulletin enregistré. Une note explique que les
lignes masquées restent incluses dans les totaux.
Le PDF de l'espace personnel Enseignant est hors de ce changement.

## Vérification locale

Depuis `sicoreBack` :

```powershell
& ..\.tools\php\php.exe artisan test --filter=PayrollPayslipPresentationTest
```

Depuis `sicoreFront` :

```powershell
& ..\.tools\php\php.exe artisan test --filter=PayrollPayslipDisplayTest
```

Le test d'intégration `PayrollPayslipDisplayIntegrationTest` vérifie le calcul,
la persistance de l'instantané et la réponse du contrôleur avec les migrations
réelles. Il est ignoré sous SQLite : certaines migrations du projet interrogent
`information_schema`, propre à MySQL/MariaDB. Pour le lancer, utiliser une base
de test dédiée et vide, jamais la base locale de travail :

```powershell
# Terminal dédié, depuis sicoreBack. La base a été créée pendant la vérification locale.
$env:APP_ENV = 'testing'
$env:DB_CONNECTION = 'mysql'
$env:DB_DATABASE = 'sicore_payslip_display_test'
$env:CACHE_STORE = 'array'
$env:SESSION_DRIVER = 'array'
& ..\.tools\php\php.exe vendor/phpunit/phpunit/phpunit --no-configuration --bootstrap vendor/autoload.php tests/Feature/PayrollPayslipDisplayIntegrationTest.php
# Fermer ce terminal après le test pour ne pas réutiliser ces variables.
```

Vérification dans l'application : choisir un code déjà présent dans un bulletin,
modifier son libellé via Paramétrage et consulter un bulletin antérieur sans
instantané ; le nouveau libellé doit apparaître sans changer les montants.
Sur un bulletin nouvellement calculé, le libellé doit rester identique après
une nouvelle modification du catalogue. L'option d'affichage existante en base
est respectée ; son éventuelle exposition dans le formulaire Paramétrage relève
de l'équipe responsable de ce module.

Pour une revue limitée à ce sujet, exclure les fichiers d'installation locale
préexistants (`.env` du frontend). Aucun fichier PDF personnel n'est ajouté au dépôt.
