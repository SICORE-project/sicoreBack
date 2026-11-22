# Gestion de la paie : périodes et bulletin de solde

## Liste des enseignants dans Bulletins

La liste affiche immédiatement les enseignants actifs enregistrés dans
Personnel, sans demander de configuration individuelle ni de génération.
Les filtres IA/IEF/matricule et le périmètre de l'utilisateur restent appliqués.
Une ligne sans bulletin porte la référence « Non calculé » et des tirets pour
le brut, les retenues et le net : cette consultation ne crée ni bulletin ni
montant en base. Les bulletins existants conservent leur bouton Consulter.
La colonne Paiement et l'action Marquer payé ont été retirées de cette liste.
Les fonctions de paiement et les autres rapports ne sont pas modifiés.

## Périodes issues du CRUD Paramétrage

## Générer depuis les dossiers Personnel

Le bouton « Générer des bulletins » ouvre un formulaire commun :

- Toute une IA, avec IEF et corps/statut PC/VAC facultatifs ;
- Un enseignant choisi dans la liste par son matricule, avec IA, IEF, statut,
  diplôme et catégorie repris en lecture seule depuis Personnel.

Choisir l'année académique et le mois configurés dans Paramétrage. Le salaire
du diplôme administratif est repris sans inventer une catégorie salariale.
Le dossier enseignant n'est pas réenregistré et aucun second profil n'est créé.
La première ligne d'un bulletin généré depuis ce dossier est « SALAIRE BRUT » :
elle reprend intégralement `diplomes.salaire_brut`, avec la source `diploma_salary`.
La rubrique SALAIRE_BASE/10 ne peut ni modifier ni masquer cette ligne.
Selon la confirmation de l'utilisateur, les huit augmentations PC sont lues
dans Rubriques de paie et ajoutées au salaire du diplôme, sur des lignes
distinctes. Leurs montants sont inclus dans les gains et les totaux brut/net.
Les autres gains et retenues du moteur restent appliqués. Les anciennes grilles
conservent leur règle historique de décomposition pour ne pas compter deux fois
des augmentations déjà incluses dans leur salaire. Les lignes d'augmentation
restent visibles sur les anciens bulletins.
Le code technique SALAIRE_BASE est conservé pour la compatibilité des rapports.
Les bulletins historiques restent figés : utiliser une génération/recalcul
autorisé pour adopter cette présentation et ce salaire, sans écraser les
périodes validées, clôturées ou les bulletins payés.
Les anciens profils de paie restent compatibles lorsqu'aucun diplôme
administratif n'est associé. Un dossier incomplet ou une rubrique sans montant
bloque la sélection avec un message explicite et sans génération partielle.

La génération ciblée conserve les bulletins des autres enseignants et IA,
puis recalcule les totaux et le contrôle d'intégrité de la période complète.
Une période validée/clôturée ou un bulletin sélectionné déjà payé reste protégé.

### Configuration locale encore nécessaire

Les rubriques IMPR, TRIMF, IPM et CHECKOFF_UES sont actives et mensuelles.
À la demande explicite de l'utilisateur, leurs montants ont été enregistrés
directement dans la base locale le 26 octobre 2026 : respectivement 55 600,
500, 4 500 et 1 000 FCFA, issus du bulletin fourni. Aucun seeder n'est exécuté.
Sur une autre installation, renseigner les montants via le CRUD existant.

La table `periode_de_paies` (code, libellé, `numero_mois`) est le référentiel
des 12 mois. Le numéro du mois (1 à 12, unique) est choisi dans une liste dans
Paramétrage → Périodes de paie ; il n'est plus déduit du code ni du libellé.
Une ligne de ce référentiel sert pour toutes les années : elle ne porte ni
dates ni lien vers une période datée (colonnes supprimées le 22/11/2026).

Rattachement à l'année académique : `payroll_periods.annee_academique_id`
(clé étrangère), posé à l'ouverture du mois dans Paie → Édition. La règle
d'appartenance, codée uniquement dans `PayrollMonthResolver::monthStart()`, est :
un mois appartient à l'année s'il chevauche [date_debut, date_fin]. Exemple :
pour une année du 21/10/2026 au 22/06/2027, octobre 2026 et juin 2027 en font
partie, juillet 2027 non. Ensuite, tous les écrans (Édition, État des salaires,
suppression d'une année) lisent la clé étrangère, jamais les dates. Une année
ne peut pas être modifiée si ses nouvelles dates excluent une période déjà
rattachée.

`PayrollPeriodCatalogue` matérialise le calendrier dans `payroll_periods`
pour les besoins du moteur (dates, calcul, validation, clôture et clés des
présences/bulletins). Il ne crée jamais de référence dans Paramétrage.
Les identifiants et les états existants sont réutilisés, les mois non configurés
ne sont pas proposés. Une année clôturée ne crée pas de nouvelle période.
Les données financières et les bulletins historiques ne sont pas supprimés.

Il n'y a plus de formulaire, bouton ou endpoint `create-period` dans Paie.
Les mois des avances/retenues Tabaski proviennent du même catalogue.
Les historiques restent stockés en base même si une référence est supprimée
dans le CRUD de Paramétrage ; ils restent consultables par l'URL du bulletin.

## Seeders

Les deux seeders ajoutés pour l'installation et la démonstration locales ont
été retirés. Les données déjà enregistrées sont conservées, aucune commande
de réinitialisation de la base n'est utilisée. Les seeders appartenant à
Administration/Paramétrage et les fixtures de tests restent inchangés.
Les nouvelles données passent par leurs CRUD respectifs.

## Structure du bulletin PC

La vue suit la première page de BULLETIN BAYE.pdf :
en-tête République/Bulletin de solde/Validité/Matricule ; identité, IA, IDEN,
établissement, corps, catégorie et encadré réclamation ; tableau Dates,
Origine, Echéances, Gains, Retenues ; TOTAL ; N° Emission, N° Ordre, Date,
Imputation, Bulletin Négatif, NET A PERCEVOIR ; talon Visa/Timbre à date,
Matricule, banque et net ; pied d'édition à l'identité SICORE.

Les numéros d'émission, d'ordre, d'imputation ou les dates d'échéance non
fournis par l'API ne sont pas inventés. Les cellules restent vides ou affichent
un tiret. Les données personnelles du PDF ne sont pas recopiées en base.
La part employeur reste consultable à l'écran, hors net et hors document
imprimé, pour respecter la structure du bulletin fourni.

`config/payroll_bulletin.php` contient exclusivement les correspondances de
présentation des codes 10, 50–57, 90, 91, 91A, I000, I080, I061, I090, I790,
R90 et les libellés exacts du PDF, y compris ses abréviations et graphies.
**Un libellé configuré via le CRUD reste prioritaire.** Les anciens libellés
de démonstration déjà configurés ne sont pas écrasés : leur correction exacte
se fait dans le CRUD Rubriques de paie, avant un nouveau calcul.

Le code 91 permet notamment de configurer INDEMNITE COMPENSATION sans dépasser
la limite de 20 caractères du catalogue. Les nouveaux bulletins conservent
leurs réglages d'affichage au calcul ; les montants ne sont jamais recalculés
par la vue. Voir aussi `bulletin-parametrage.md`.

Les styles écran, mobile et impression A4 sont dans `payroll-bulletin.css`,
chargé uniquement par cette vue. Aucun écran, contrôleur, modèle ou migration
des modules Administration/Paramétrage n'est modifié.

## Vérifications ciblées

Depuis sicoreBack :

```powershell
& ..\.tools\php\php.exe artisan test --filter='PayrollPeriodCatalogueTest|PayrollPayslipPresentationTest'
```

Depuis sicoreFront :

```powershell
& ..\.tools\php\php.exe artisan test --filter=PayrollPayslipDisplayTest
```

Le test d'intégration MySQL est décrit dans `bulletin-parametrage.md`.
Le calendrier de test est créé dans les tests, jamais injecté par un seeder
dans une base de travail. Dans la base locale de cette installation, OCTOBRE
a été ajouté par l'API CRUD existante et les dix bulletins initiaux sont conservés.

## Affectation de l'enseignant et IA du bulletin (22/11/2026)

L'IEF est la référence de l'affectation. L'IA de l'enseignant est toujours
celle de son IEF : les formulaires du personnel refusent une IEF d'une autre IA
(`ValidatesTeacherHierarchy`), et le trait `DerivesTeacherIaFromIef`, utilisé
par les trois modèles de la table `enseignants`, recalcule `ia_id` à chaque
sauvegarde (imports et services compris).

Au calcul, l'IA et l'IEF sont recopiées sur le bulletin
(`payroll_payslips.ia_id` / `ief_id`). Les rapports d'un mois payé (portée
IA, État des salaires, Résumé par IEF, filtres IA/IEF de l'Édition) lisent ces
colonnes : un enseignant muté reste, pour ses anciens bulletins, dans l'IA qui
l'a payé. Sans bulletin, c'est l'affectation actuelle qui compte. Les rapports
d'effectif (« workforce », statistiques des enseignants) restent sur
l'affectation actuelle. Les bulletins antérieurs à la migration ont reçu
l'affectation qu'avait l'enseignant le 22/11/2026.
