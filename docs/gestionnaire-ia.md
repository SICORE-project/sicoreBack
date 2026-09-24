# ADM-INT-003 — Interface du gestionnaire IA

## Comportement livré

- Connexion vers `/dashboard`, affichage du gestionnaire et de « Périmètre : IA de … ».
- Rattachement obligatoire à une structure IA active et à un `users.ia_id` cohérent. La création et la modification administratives déduisent l’IA de la structure ; un rattachement incohérent est rejeté. Un compte invalide ne peut pas se connecter.
- Tableau de bord : agents, fonctionnaires, contractuels/vacataires, répartitions par IEF et lieu de service, bulletins générés/restants, dernières opérations. La masse salariale nécessite `paie.masse_salariale.read`.
- Personnel : recherche, filtres IEF/lieu limités à l’IA, pagination et consultation du dossier. Le workflow de recrutement existant reste soumis à ses permissions.
- Paie : consultation des bulletins, état des salaires et sommes perçues selon leurs permissions, sélection de période, consultation individuelle et export CSV autorisé.
- Aucun sélecteur d’IA. Paramétrage et gestion utilisateur sont masqués et leurs endpoints nationaux sont refusés. Les pages nationales non adaptées au périmètre IA, dont les indemnités, ne sont pas exposées à ce profil.

## Contrat backend

Les routes `/api/ia/*` sont protégées par Sanctum, le rôle `gestionnaire_ia` et `IaAccess`. `IaScope` impose l’IA issue du compte, jamais celle choisie par le client.

| Endpoint GET | Permission |
| --- | --- |
| `/api/ia/dashboard` | Indicateurs filtrés par les permissions du compte |
| `/api/ia/enseignants`, `/api/ia/enseignants/{id}`, `/api/ia/referentiels` | `enseignants.read` |
| `/api/ia/paie`, `/api/ia/paie/{id}` | `paie.bulletins.read` |
| `/api/ia/paie?view=paid` | `paie.bulletins.read` et `paie.sommes_percues.read` |
| `/api/ia/paie?view=salaries` | `paie.bulletins.read` et `paie.etat_salaires.read` |
| `/api/ia/paie/export` | `paie.bulletins.export` |

Les identifiants d’une autre IA (IA, IEF, lieu, enseignant, bulletin, membre ou document de recrutement) sont refusés en 403. Un document commun à un lot contenant des agents d’autres IA est également refusé. Les enseignants supprimés sont exclus des requêtes.

`personnel_audit_logs` conserve l’utilisateur, la méthode/action, le chemin concret de la ressource et les noms des champs modifiés. Les refus 403 sont journalisés avec `acces_refuse`. Les contenus des documents, tokens et mots de passe ne sont pas journalisés.

## Mise en service et recette

Appliquer les migrations backend dans l’environnement de déploiement, notamment la table d’audit et le catalogue des permissions de paie. La migration `2026_09_21_030000_register_ia_optional_payroll_permissions` crée les permissions d’export et de masse salariale sans les attribuer automatiquement. Les migrations ne sont pas exécutées sur la base applicative pendant cette implémentation.

Pour un ancien compte sans `ia_id`, réenregistrer sa structure IA via l’administration, puis se reconnecter. L’attribution des permissions reste administrable. Le seeder du rôle IA ne lui attribue plus les permissions de paramétrage.

Tests ciblés exécutés :

```powershell
# sicoreBack
php artisan test tests/Feature/IaAccessTest.php tests/Feature/RecruitmentWorkflowTest.php
php artisan test tests/Feature/AgentDrhTest.php tests/Feature/DirecteurDrhTest.php

# sicoreFront
php artisan test tests/Feature/IaWorkspaceTest.php tests/Feature/IaDashboardTest.php tests/Feature/PermissionNavigationTest.php tests/Feature/AdministrationDashboardTest.php
```

63 tests distincts passent : 45 backend et 18 frontend. La suite backend globale reste bloquée par des marqueurs de conflit préexistants dans `tests/Feature/AuthApiTest.php`.

Recette navigateur restant à effectuer : ouvrir les pages avec un compte IA réel, vérifier le rendu mobile, les permissions attribuées et les données de la base cible. La base cible n’a pas été modifiée et aucune publication n’a été effectuée.
