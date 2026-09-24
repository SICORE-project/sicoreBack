# Agent DECPC — ADM-INT-002

## Installation

Exécuter les migrations puis `php artisan db:seed --class=AgentDecpcSeeder`.
Le seeder est aussi appelé par DatabaseSeeder. Il conserve les permissions complémentaires et ne crée aucun compte.
Associer le rôle `agent_decpc` à un utilisateur et lui affecter un lieu de service actif de type `DECPC`.

## Périmètre retenu

La configuration du lieu de service détermine le périmètre, comme pour la DRH : restrictions individuelles IEF/IA prioritaires, puis périmètre national explicitement configuré, puis IEF/IA de la structure. Une structure absente, inactive, incompatible ou sans périmètre exploitable ne donne accès à aucun dossier.

Le personnel est filtré sur son IEF/IA. Les indemnités génériques sont filtrées sur l'utilisateur bénéficiaire (`utilisateur_id`), avec les mêmes restrictions IEF/IA. Ce champ représente le bénéficiaire, jamais l'agent traitant. Les bénéficiaires doivent donc avoir leur rattachement organisationnel renseigné. Le périmètre national autorise tous les bénéficiaires non supprimés.

## Contrat frontend

- Connexion : `redirect_to = /decpc/dashboard`, contexte dans `decpc`.
- Session : `GET /api/me`, contexte dans `user.decpc`.
- Tableau de bord : `GET /api/decpc/dashboard`, résultat dans `data`.
- Le contexte contient `dashboard_path`, `dashboard_api`, `perimetre`, `permissions`, `modules`.
- Construire le sidebar à partir de `modules` et conditionner les actions aux permissions.
- Indicateurs : total_agents, indemnites_en_attente (brouillon/calculé), indemnites_validees, indemnites_rejetees, montant_indemnites_en_cours, indemnites_par_type, derniers_dossiers (10 maximum).

Le modèle actuel ne comporte pas de statut « retourné » : aucun compteur artificiel n'est ajouté pour ce statut.

## Permissions et endpoints

Par défaut : `enseignants.read` et `indemnites.read` uniquement. Les permissions `create`, `update`, `delete`, `validate` sont créées et attribuables séparément pour ces deux modules. Les routes existantes du personnel déterminent les actions effectivement disponibles ; aucun nouveau circuit de validation du personnel n'est introduit.

Le personnel utilise `/api/admin/personnel/enseignants`. Les dossiers d'indemnité utilisent `/api/indemnites`, y compris calcul, simulation, validation et frais. Les types d'indemnité sont consultables. Un calcul DECPC exige un `type_indemnite_id` explicite. La validation et le rejet exigent `indemnites.validate`, y compris lorsqu'un statut est soumis via création/modification. Une indemnité validée n'est plus modifiable.

Les écritures et leurs traces dans `decpc_audit_logs` sont transactionnelles. Les traces comprennent l'acteur, la méthode, la route, les identifiants et les noms des champs modifiés ; aucune valeur sensible du corps n'est journalisée.

## Limites d'intégration

Le frontend de l'interface DECPC reste à brancher sur ce contrat. Les circuits annexes (convocations, services faits, justificatifs, missions, correction/surveillance et états de paiement) restent refusés à DECPC : leur rattachement métier exige une définition distincte avant ouverture. Les indicateurs portent donc sur la table `indemnites`, pas sur ces circuits annexes.

La paie complète conserve son contrôle existant par rôle configuré et capacité du jeton. Le paramétrage et les utilisateurs conservent leurs permissions explicites. Aucune permission complémentaire n'est accordée par défaut.

## Vérification

`php artisan test --compact tests/Feature/AgentDecpcTest.php`

Les tests couvrent navigation, périmètres, accès directs, mutation de bénéficiaire, permissions de validation, audit et annulation d'une écriture si l'audit échoue.
