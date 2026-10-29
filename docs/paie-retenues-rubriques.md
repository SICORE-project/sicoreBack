# Retenues mensuelles pilotées par les rubriques de paie

Les quatre montants ont été retirés du formulaire Configurer le profil de paie.
Le formulaire conserve IA, IEF, matricule, type PC/VAC, diplôme et catégorie.
La lecture des montants et les contrôles sont limités au module Gestion de la
paie ; le CRUD et les modèles Administration/Paramétrage ne sont pas modifiés.

## Configuration dans le CRUD existant

Dans Paramétrage → Rubriques de paie, compléter le champ **Montant** :

| Retenue | Code technique ou code officiel accepté | Profils concernés |
|---|---|---|
| IMPR | IMPR ou I061 | PC et VAC |
| TRIMF | TRIMF ou I090 | PC et VAC |
| IPM | IPM ou I080 | PC |
| Check-off UES | CHECKOFF_UES ou I790 | PC |

Chaque rubrique doit être une **retenue**, de périodicité **mensuelle**, active,
avec un montant renseigné. Zéro est accepté explicitement ; un montant vide
n'est jamais converti implicitement en zéro. Les codes officiels commencent
par la lettre I majuscule. Ne pas créer deux configurations contradictoires
sous les deux codes d'une même retenue.

Le CRUD actuel contient un montant fixe par rubrique : il est commun aux
profils concernés. Il ne définit pas de montant différent selon l'enseignant
ou le type d'engagement, ni de barème fiscal personnel. Aucune formule n'est
inventée ou évaluée pour déduire ces montants.

La configuration individuelle et le calcul donnent un message regroupant
les rubriques à compléter si une valeur manque ou si la configuration est
incohérente. IPM et Check-off ne sont pas appliqués aux VAC, conformément au
fonctionnement du moteur PC/VAC existant.

## Calcul et historique

`PayrollRubricAmounts` relit le catalogue à chaque configuration/calcul.
Les anciennes colonnes de montants individuels ne sont plus utilisées pour
calculer les profils PC/VAC, ni proposées dans l'API de formulaire.
Un ancien client qui essaie de transmettre ces champs reçoit une erreur de
validation. Les codes techniques et officiels sont réservés au moteur et ne
peuvent pas être ajoutés une deuxième fois comme élément manuel.

Les montants, identifiants, codes et libellés des rubriques utilisés sont
enregistrés dans `profile_snapshot.deduction_rubrics`. Les lignes concernées
portent la source `payroll_rubric`. Les bulletins déjà enregistrés ne changent
pas lors d'une modification du catalogue. Un recalcul autorisé d'une période
ouverte ou calculée reprend les nouveaux montants ; les contrôles de validation
et de clôture existants restent applicables.

Le rapport Mutuelles de santé lit aussi le montant IPM du catalogue, pour
éviter de présenter une ancienne valeur individuelle comme configuration actuelle.

Aucune migration, aucun seeder et aucune mise à jour automatique de montant
dans la base locale ne sont nécessaires pour installer le code. Le 26 octobre
2026, à la demande de l'utilisateur, les quatre montants ont été enregistrés
directement dans la base locale depuis l'exemple PDF : IMPR 55 600, TRIMF 500,
IPM 4 500 et Check-off 1 000 FCFA. Aucun seeder ni export SQL n'est ajouté.
Ces données locales ne sont pas transférées par Git et ne recalculent pas les
bulletins existants. Elles restent modifiables dans le CRUD ; le montant fixe
d'une rubrique est commun aux profils concernés, sans personnalisation fiscale.

## Vérifications

Depuis sicoreBack :

```powershell
& ..\.tools\php\php.exe artisan test --filter=PayrollRubricAmountsTest
```

Le test MySQL `PayrollPayslipDisplayIntegrationTest` vérifie également un calcul
PC et VAC, les montants issus du catalogue, la conservation d'un ancien
bulletin et la prise en compte d'un changement lors d'un recalcul.
Utiliser exclusivement la base de test dédiée décrite dans
`bulletin-parametrage.md`.
