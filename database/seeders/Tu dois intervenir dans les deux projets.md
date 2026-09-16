Tu dois intervenir dans les deux projets suivants :

* **SICORE Front**
* **SICORE Back**

La fonctionnalité **Gestion de la paie** existe déjà. Les tables, les modèles, les relations et une partie des interfaces existent également. Il ne faut donc pas reconstruire la fonctionnalité ni créer une architecture parallèle.

Ta mission consiste à analyser l’existant, puis à réorganiser et compléter proprement la fonctionnalité selon les exigences ci-dessous.

Le projet est développé par plusieurs personnes. Les modifications doivent être limitées, isolées et faciles à fusionner.

# 1. Gestion obligatoire des branches Git

Dans les deux projets, créer ou utiliser une branche portant exactement le nom :

```text
dev-bayesaliou
```

Cette branche doit partir de la dernière version de `TestIntegration`.

## Procédure dans SICORE Back

```powershell
cd C:\xampp\htdocs\SICORE-BACK-BAYE

git status
git fetch origin --prune
git switch TestIntegration
git pull --ff-only origin TestIntegration
git branch -a
```

Si `dev-bayesaliou` n’existe ni localement ni à distance :

```powershell
git switch -c dev-bayesaliou
```

Si `origin/dev-bayesaliou` existe déjà, ne la supprime pas et ne la recrée pas. Utilise-la :

```powershell
git switch dev-bayesaliou
```

Si elle existe uniquement à distance :

```powershell
git switch -c dev-bayesaliou --track origin/dev-bayesaliou
git pull --ff-only origin dev-bayesaliou
```

## Procédure dans SICORE Front

Appliquer la même procédure :

```powershell
cd C:\xampp\htdocs\SICORE-FRONT-BAYE

git status
git fetch origin --prune
git switch TestIntegration
git pull --ff-only origin TestIntegration
git branch -a
```

Créer ou récupérer ensuite `dev-bayesaliou` de la même manière.

## Règles Git obligatoires

* Ne jamais travailler directement dans `TestIntegration`.
* Ne pas supprimer ou recréer une branche distante existante.
* Ne jamais utiliser `git push --force`.
* Ne pas utiliser `git reset --hard`.
* Ne pas supprimer les modifications d’un autre développeur.
* Ne pas pousser de fichiers sans rapport avec Gestion de la paie.
* Faire un commit séparé dans le Front et dans le Back.
* Vérifier `git diff` avant chaque commit.
* Pousser les deux branches vers `origin/dev-bayesaliou`.

À la fin du travail :

```powershell
git push -u origin dev-bayesaliou
```

# 2. Réorganisation du menu « États de présence »

Dans le module existant **Gestion de la paie**, organiser le menu afin que son titre principal soit :

**États de présence**

Ce titre doit ouvrir un menu déroulant contenant exactement les éléments suivants :

1. Avance Tabaski
2. Retenue Tabaski
3. Retenue sur rappel
4. Saisir le nombre de jours travaillés
5. Exempter un enseignant d’une avance ou d’une retenue
6. Clôture de la paie
7. Sommes perçues
8. Génération du montant des heures supplémentaires
9. Génération individuelle de la paie

Avant de créer un composant ou une route, recherche ceux qui existent déjà.

Il faut :

* Réutiliser le menu existant.
* Réutiliser les routes existantes.
* Réutiliser les pages existantes.
* Conserver les permissions existantes.
* Conserver le comportement responsive.
* Respecter le design actuel de Gestion de la paie.
* Éviter les doublons dans le menu.
* Ne pas créer un deuxième module États de présence.

Pour les éléments autres que **Avance Tabaski** et **Retenue Tabaski**, conserve leur fonctionnement existant. N’invente pas de nouvelle logique métier.

# 3. Avance Tabaski

La fonctionnalité existe déjà. Analyse l’implémentation actuelle et adapte-la au besoin sans la réécrire entièrement.

Le formulaire doit contenir les champs suivants.

## Corps de l’enseignant

Permettre de sélectionner un corps parmi :

* `VAC` : Vacataire
* `PC` : Professeur contractuel

Les corps enseignants doivent provenir des tables, modèles et endpoints existants.

Ne pas coder `VAC` et `PC` directement dans plusieurs composants frontend. Charger les données depuis le backend ou depuis le mécanisme de référentiel déjà utilisé dans le projet.

## IA

* Charger toutes les IA existantes depuis le backend.
* Afficher les IA sous forme de cases à cocher.
* Permettre de sélectionner une ou plusieurs IA.
* Ajouter « Tout sélectionner » et « Tout désélectionner » si les composants actuels le permettent.
* Enregistrer les identifiants des IA sélectionnées.
* Ne jamais coder les IA en dur dans le frontend.

## Année académique

* Charger les années académiques depuis les tables et modèles existants.
* Afficher une liste déroulante.
* Permettre de sélectionner une année académique.
* Enregistrer l’identifiant de l’année académique.

## Période de la Tabaski

Afficher les 12 mois :

* Janvier
* Février
* Mars
* Avril
* Mai
* Juin
* Juillet
* Août
* Septembre
* Octobre
* Novembre
* Décembre

L’utilisateur doit pouvoir choisir un seul mois.

Le mois de la Tabaski ne doit pas être fixé définitivement, car la Tabaski peut tomber sur un mois différent selon l’année.

## Montant de l’avance

* Afficher par défaut `100 000 FCFA`.
* Le champ doit être prérempli avec la valeur numérique `100000`.
* L’utilisateur doit pouvoir modifier le montant.
* Le montant doit être strictement positif.
* Enregistrer une valeur numérique sans le texte `FCFA`.
* Appliquer les validations dans le Front et dans le Back.

# 4. Retenue Tabaski

La fonctionnalité Retenue Tabaski existe déjà. Il faut l’organiser et la compléter sans créer une nouvelle fonctionnalité parallèle.

Le formulaire doit contenir :

* Le corps de l’enseignant
* Une ou plusieurs IA
* L’année académique
* Le montant
* La période de retenue

## Montant

* Afficher `100 000 FCFA` par défaut.
* Préremplir le champ avec `100000`.
* Permettre la modification du montant.
* Accepter uniquement une valeur numérique strictement positive.

## Période de retenue

La retenue doit être configurée sur exactement **10 mois de paie**.

* Afficher les mois sous forme de cases à cocher.
* Permettre de sélectionner les mois concernés.
* Exiger exactement 10 mois distincts.
* Empêcher l’enregistrement si moins de 10 mois sont sélectionnés.
* Empêcher l’enregistrement si plus de 10 mois sont sélectionnés.
* Enregistrer les mois sélectionnés sous une forme structurée.
* Vérifier cette règle dans le frontend et dans le backend.
* Ne pas inventer un calcul automatique de la mensualité si cette règle n’existe pas dans le code actuel.

# 5. Réutilisation des tables et modèles existants

Les tables et modèles suivants existent déjà :

* IA
* Années académiques
* Corps d’enseignement
* Éléments liés à Gestion de la paie

Il est donc interdit de :

* Recréer ces tables.
* Créer de nouveaux modèles représentant les mêmes données.
* Créer des migrations concurrentes.
* Renommer les tables partagées.
* Modifier les migrations déjà exécutées.
* Dupliquer les relations existantes.
* Créer de nouveaux endpoints si les endpoints nécessaires existent déjà.
* Modifier le fonctionnement des autres modules.

Utilise les identifiants et relations existants.

Si un champ manque réellement dans une table appartenant à Gestion de la paie, vérifie d’abord qu’aucun autre développeur ne travaille dessus. Ajoute ensuite une nouvelle migration ciblée, sans modifier une ancienne migration.

# 6. Utilisation sécurisée des seeders

Les tables et modèles existent déjà. Utilise les seeders uniquement pour ajouter les données manquantes dans les tables existantes.

Avant de créer un seeder :

1. Recherche les seeders existants.
2. Vérifie les données déjà insérées.
3. Vérifie les codes et libellés utilisés par les autres modules.
4. Évite de recréer les mêmes données.

Si un seeder propre à Gestion de la paie est nécessaire, crée un seeder principal isolé :

```text
GestionPaieSeeder
```

Ce seeder peut appeler des sous-seeders propres au module de paie.

Exemple d’organisation :

```php
public function run(): void
{
    $this->call([
        ParametreAvanceTabaskiSeeder::class,
        ParametreRetenueTabaskiSeeder::class,
    ]);
}
```

Les noms doivent être adaptés aux conventions réelles du projet.

## Règles obligatoires pour les seeders

* Utiliser les modèles existants.
* Utiliser `updateOrCreate()` ou `firstOrCreate()`.
* Utiliser un code fonctionnel stable pour identifier les données.
* Ne pas dépendre d’un identifiant numérique fixe.
* Ne jamais utiliser `truncate()`.
* Ne jamais supprimer les données existantes.
* Ne pas écraser les données ajoutées par d’autres développeurs.
* Permettre plusieurs exécutions sans créer de doublons.
* Ne pas recréer les IA, années académiques ou corps déjà présents.
* Ne pas utiliser `migrate:fresh`.

Ajouter ensuite uniquement l’appel suivant dans `DatabaseSeeder`, en respectant la syntaxe et l’organisation existantes :

```php
$this->call(GestionPaieSeeder::class);
```

La modification de `DatabaseSeeder` doit être minimale. Ne déplace pas et ne supprime pas les autres appels.

Pendant le développement, le seeder doit pouvoir être testé directement :

```powershell
php artisan db:seed --class=GestionPaieSeeder
```

# 7. Prévention des conflits avec les autres développeurs

Le code est partagé entre plusieurs développeurs.

Tu dois impérativement :

* Lire les fichiers avant de les modifier.
* Faire des changements ciblés.
* Ne pas reformater des fichiers complets.
* Ne pas modifier le code d’un autre module.
* Ne pas réaliser de refactoring général.
* Ne pas renommer les modèles, services ou composants partagés.
* Ne pas modifier inutilement les dépendances.
* Ne pas modifier les fichiers de verrouillage sans nécessité.
* Ne pas remplacer un fichier complet pour une petite modification.
* Ne pas supprimer une fonctionnalité existante.
* Ne pas résoudre un conflit en supprimant le travail d’un autre développeur.

Si une modification risque de toucher un fichier fortement partagé, cherche une solution isolée :

* Nouveau composant propre à Gestion de la paie
* Nouveau service propre à Gestion de la paie
* Nouveau seeder propre à Gestion de la paie
* Nouvelle migration ciblée
* Modification minimale d’une route ou d’un fichier central

# 8. Fidélité à Gestion de la paie

La nouvelle organisation doit conserver :

* La mise en page existante
* Les composants existants
* Les couleurs et styles existants
* Les permissions existantes
* Les routes existantes
* Les services existants
* Les conventions de nommage
* La gestion des erreurs
* La gestion du chargement
* Les tableaux existants
* La pagination existante
* Les messages de confirmation
* Le système de validation

Ne crée pas une nouvelle architecture parallèle.

# 9. Tests obligatoires

Vérifie les points suivants :

* Le menu principal affiche « États de présence ».
* Le menu déroulant contient les neuf éléments.
* Aucun doublon n’apparaît dans la navigation.
* Les anciennes fonctionnalités restent accessibles.
* Les corps enseignants sont chargés depuis le backend.
* Toutes les IA existantes sont affichées.
* Les années académiques sont chargées depuis le backend.
* L’Avance Tabaski permet de choisir un seul mois parmi les 12 mois.
* Le montant par défaut est `100 000 FCFA`.
* Le montant reste modifiable.
* La Retenue Tabaski exige exactement 10 mois.
* Les validations frontend et backend fonctionnent.
* Les seeders peuvent être exécutés plusieurs fois sans doublons.
* Les autres fonctionnalités de Gestion de la paie continuent de fonctionner.
* Le build du Front réussit.
* Les tests disponibles du Back réussissent.

# 10. Commit et push obligatoires

Avant de faire les commits, exécute dans chaque projet :

```powershell
git status
git diff
git diff --stat
```

Ne sélectionne que les fichiers liés à cette tâche.

Utilise des messages de commit clairs.

Pour le Front :

```powershell
git add <uniquement-les-fichiers-concernés>
git commit -m "feat(paie): reorganiser le menu etats de presence"
git push -u origin dev-bayesaliou
```

Pour le Back :

```powershell
git add <uniquement-les-fichiers-concernés>
git commit -m "feat(paie): adapter les avances et retenues Tabaski"
git push -u origin dev-bayesaliou
```

Il est strictement interdit d’utiliser :

```powershell
git add .
git push --force
git reset --hard
```

# 11. Compte rendu final

À la fin, fournis obligatoirement :

1. La branche utilisée dans le Front.
2. La branche utilisée dans le Back.
3. La liste exacte des fichiers créés.
4. La liste exacte des fichiers modifiés.
5. La justification de chaque modification.
6. Les composants existants réutilisés.
7. Les modèles et tables existants réutilisés.
8. Les routes et endpoints réutilisés ou ajoutés.
9. Les seeders créés ou réutilisés.
10. La modification exacte de `DatabaseSeeder`.
11. Les tests exécutés et leurs résultats.
12. Les identifiants des commits Front et Back.
13. La confirmation du push vers `origin/dev-bayesaliou` dans les deux projets.
14. Le résultat final de `git status`.
15. Le résultat de `git diff --stat` avant les commits.
16. Les éventuels éléments qui nécessitent encore une coordination avec les autres développeurs.

N'implementer des fonctionnalités que les autres doivent nous donner, implementer uniquement la partie gestion de la paie, pour les autres utiliser les seeders uniquement, sans sortir de ton domaine, nous ne voulons pas toucher les parties des autres codeurs