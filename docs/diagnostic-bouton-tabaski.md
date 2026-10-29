# Pourquoi « Appliquer collectivement » peut être absent après un clone

Le bouton est fourni par l'API, pas ajouté automatiquement par la vue.
Le fait d'avoir les mêmes branches n'implique pas d'avoir les mêmes données
MySQL, le même compte connecté ou la même URL d'API.

## Chaîne dans le code

1. `app/Services/PayrollPageService.php`, méthode `elementReport` : pour
   TABASKI_AVANCE, l'action `apply-tabaski-advance` est créée avec le libellé
   « Appliquer collectivement ».
2. Dans `page`, le bloc `$missingReferences` remplace ensuite les actions
   par Exporter uniquement s'il manque une année académique non clôturée,
   un statut enseignant dont le libellé contient « contractuel » ou
   « vacataire », ou une IA. Un message `notice` indique les données manquantes.
   Les statuts sont lus dans `statuts_enseignant`, pas `corps_enseignant`.
3. Frontend : `app/View/Components/ModulePage.php` reprend `actions` depuis
   la réponse API. Si aucune donnée API n'est disponible, les actions sont vides.
4. `resources/views/components/module-page.blade.php` parcourt les actions
   et affiche le bouton avec `data-payroll-action`.
5. `config/payroll-forms.php` définit le formulaire `apply-tabaski-advance` ;
   `public/assets/js/payroll.js` ouvre la modale et envoie la demande.

## Diagnostic sur la machine concernée

Comparer les versions dans chaque dépôt :

```powershell
git branch --show-current
git log -1 --oneline
git status --short
```

Versions publiées lors de ce diagnostic : backend `f4b7905`, frontend
`8f9f9c5`, branche `bayesaliou-dev-sicore`.
Un commit plus récent n'est pas nécessairement incorrect : comparer alors
le bloc des actions Tabaski et les éventuels conflits de fusion.

Exécuter ces lectures sur la base réellement utilisée par leur backend :

```sql
SELECT id, libelle, date_debut, date_fin, est_cloturee
FROM annee_academiques WHERE est_cloturee = 0;

SELECT id, code, libelle FROM statuts_enseignant
WHERE LOWER(libelle) LIKE '%contractuel%'
   OR LOWER(libelle) LIKE '%vacataire%';

SELECT id, code, libelle FROM ias;
```

Si une requête ne renvoie rien, créer les références avec les CRUD existants
dans Paramétrage. Aucun seeder et aucune réinitialisation de base ne sont requis.
Pour utiliser ensuite le formulaire, renseigner aussi les IEF, les périodes
de paie et les enseignants correspondant à la sélection. Leur absence n'est
pas la condition précise qui masque ce bouton dans `$missingReferences`.

Dans les outils réseau du navigateur, examiner la réponse de la page Paie
(le frontend appelle côté serveur `GET /api/payroll/pages/paie-avance-tabaski`).
Vérifier ces clés sur la réponse backend authentifiée :

```text
data.notice
data.actions
data.academic_years
data.teaching_statuses
data.academic_inspections
```

Si `data.actions` contient seulement Exporter et que `notice` demande une
référence, la cause est identifiée. Si elle contient `apply-tabaski-advance`
mais que le bouton est absent, vérifier la vue frontend et ses caches.
Un bouton présent qui ne s'ouvre pas relève plutôt du JS/formulaire ; vérifier
la console et le chargement de `payroll.js`.

## API, cache et droits

Le frontend utilise `API_BASE_URL` dans son `.env`. Celui-ci reste local et
n'a pas été poussé. L'URL doit désigner le backend réellement lancé par
l'autre groupe, avec son port et le suffixe `/api`.
Après correction de la configuration, dans chacun des deux dépôts :

```powershell
php artisan optimize:clear
```

Utiliser le binaire PHP de leur installation, redémarrer les serveurs puis
actualiser le navigateur avec Ctrl+F5. Se déconnecter/reconnecter si les
permissions du compte ont changé.

`EnsurePayrollAccess` exige `paie.bulletins.generate` pour appliquer l'action,
ou le rôle super_admin. Une réponse HTTP 403 au clic indique un problème
d'autorisation, pas la disparition due à `$missingReferences`.
`PayrollPageController::page` restreint les pages et actions du rôle
gestionnaire_ia : Avance Tabaski n'est pas dans sa liste des pages autorisées
actuelle. Ne pas contourner ces contrôles pour afficher artificiellement le
bouton. Tester avec le compte du rôle prévu pour la gestion de paie.

## Explication à transmettre au groupe

« Les branches contiennent le code, pas les données locales de Paramétrage.
Le backend masque Appliquer collectivement lorsqu'il manque une année
académique non clôturée, un statut PC/VAC ou une IA. Vérifiez le message
au-dessus du tableau et les trois requêtes SQL. Si les références existent,
contrôlez API_BASE_URL, la réponse data.actions, les versions front/back et
les droits du compte. »

Sans leur réponse API, leurs données et leur compte, la cause exacte sur leur
machine ne peut pas être affirmée ; ce document décrit les conditions vérifiées
dans le code de la branche.
