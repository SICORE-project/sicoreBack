# ADM-INT-001 — API agent DRH

> Évolution métier : les profils DRH travaillent désormais dans le
> [circuit des lots de recrutement](recruitment-workflow.md). Leurs écritures
> manuelles sur les enseignants sont refusées, même avec une ancienne permission.
> Les sections ci-dessous décrivent le socle initial de consultation ; pour
> l'import, les OS, la DAGE, la prise de service IA et les alertes, suivre ce nouveau circuit.

## Installation

Appliquer les migrations avec `php artisan migrate`, puis exécuter
`php artisan db:seed --class=AgentDrhSeeder`. Ce seeder peut être rejoué : il
préserve les permissions complémentaires et ne modifie aucun compte existant.
Les rôles `drh` (directeur RH) et `agent_drh` partagent cette interface et les
mêmes contrôles de périmètre et d'audit. Leurs permissions existantes sont
conservées : partager une interface ne donne pas automatiquement de nouveaux droits.

Attribuer le rôle `agent_drh` et une structure active à l'utilisateur depuis
l'administration existante. La consultation `enseignants.read` est accordée
par défaut ; `enseignants.create` et `enseignants.update` sont disponibles,
mais leur attribution doit être explicite. La suppression reste facultative
via la permission existante `enseignants.delete`.

## Périmètre

Les restrictions individuelles `ief_id`, puis `ia_id`, sont prioritaires.
Sinon, une structure centrale DRH/DAGE/DECPC explicitement nationale donne
un accès national. Pour les autres structures, le rattachement IEF, puis IA,
est utilisé ; un établissement sans rattachement donne accès à son seul lieu
de service. Une structure centrale/régionale sans rattachement, une structure
inactive ou absente ne donne aucun accès. Le rôle seul n'accorde jamais un
périmètre national. Les affectations et changements de périmètre d'un dossier
sont également contrôlés.

## Intégration frontend

- `POST /api/login` retourne `redirect_to: /drh/dashboard` et `drh`.
- `GET /api/me` retourne le même contexte dans `user.drh`.
- `GET /api/drh/dashboard` retourne `data.agent`, `data.perimetre`,
  `data.permissions`, `data.modules`, `data.indicateurs`,
  `data.agents_par_lieu_service` et `data.derniers_dossiers`.
- Les dossiers utilisent les routes existantes
  `/api/admin/personnel/enseignants` (GET/POST) et `/{id}` (GET/PUT/DELETE).

Le frontend doit créer `/drh/dashboard`, utiliser `modules` pour le sidebar,
et les permissions pour les boutons. Les routes backend restent protégées
indépendamment de cet affichage. Un identifiant hors périmètre retourne 404 ;
une action sans permission ou un déplacement hors périmètre retourne 403.

La paie conserve aussi ses contrôles spécifiques de rôle et de capacité du
token (`config/payroll.php`) : la seule permission `paie.bulletins.read` ne
suffit pas à ouvrir ses API. Ne pas afficher ce module sans satisfaire ces
contrôles. Les indemnités exigent une permission complémentaire pour l'agent
DRH ; les droits de paramétrage et d'administration existants sont conservés.

## Indicateurs et audit

Un dossier actif respecte `est_actif = true` et `statut = en_activite`.
Les champs requis pour un dossier complet et les codes/libellés des corps
sont dans `config/personnel.php`. Ces règles initiales doivent être validées
avec le métier. Un corps non reconnu n'est classé dans aucune des deux
catégories, plutôt que d'être considéré automatiquement fonctionnaire.

Les consultations de listes, fiches et tableau de bord, ainsi que les
écritures réussies de l'agent DRH, sont tracées dans `personnel_audit_logs`.
La trace contient l'auteur, le dossier éventuel, la méthode HTTP, la route,
la date et les noms des champs soumis ; elle ne contient pas leurs valeurs.
Les écritures et leur audit sont transactionnels. Aucun endpoint de
consultation du journal n'est exposé à l'agent DRH.

## Validation

`php vendor/bin/phpunit tests/Feature/AgentDrhTest.php`

Les tests utilisent SQLite en mémoire. La suite complète contient déjà des
marqueurs de conflits de fusion dans `tests/Feature/AuthApiTest.php`.
Le frontend est un projet séparé et reste à raccorder à ce contrat API.

## Compte du directeur et circuit DAGE

`php artisan db:seed --class=DirecteurDrhStructureSeeder` rattache le compte
`mamedieye.dieng@sicore.sn` à la structure DRH nationale active et affiche le
profil « Directeur des ressources humaines ». Ce seeder ciblé conserve son
mot de passe et les permissions de son rôle `drh`.

Le fonctionnement demandé pour la suite est une réception des listes par la
DAGE directement dans l'application, accompagnée d'une notification interne.
L'import, l'aperçu, l'OS, la transmission et les notifications internes sont
désormais disponibles dans le module Recrutements. Aucun lot réel n'a été
importé ou transmis pendant l'installation.
