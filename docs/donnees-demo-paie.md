# Données locales de démonstration de paie

Jeu enregistré dans `sicore_local` par `PayrollDemoSeeder`.
Toutes les identités, adresses e-mail et références bancaires sont fictives.
Les captures servent de référence de présentation et de scénario, sans import
des CNI ni des comptes bancaires réels.

## Consulter les bulletins

1. Démarrer MySQL et les serveurs backend/frontend habituels.
2. Se connecter sur http://127.0.0.1:8011 avec `mamadou.diop@sicore.sn` / `password`.
3. Ouvrir Gestion de la paie → Bulletins, mois **Octobre 2026**.
4. Utiliser **Consulter** pour afficher et imprimer un bulletin.

Liens directs de cette installation :

- Liste : http://127.0.0.1:8011/paie/bulletins
- Exemple PC : http://127.0.0.1:8011/paie/bulletins/1
- Exemple VAC : http://127.0.0.1:8011/paie/bulletins/7
- État des salaires : http://127.0.0.1:8011/paie/etat-salaires

| Matricule | Profil | Scénario | Net FCFA |
|---|---|---|---:|
| DEMO-PC-001 | PC, BAC/BT, catégorie 1 | Bulletin de référence, retenue Tabaski 10 000 | 260 670 |
| DEMO-PC-002 | PC, CAP, catégorie 1 | Rappel 15 000 | 270 964 |
| DEMO-PC-003 | PC, BEP, catégorie 1 | Retenue exceptionnelle 2 500 | 262 064 |
| DEMO-PC-004 | PC, BTS/DUEL/DUES, catégorie 1 | Sans élément variable | 288 725 |
| DEMO-PC-005 | PC, licence, catégorie 2 | Barème IRD licence | 309 019 |
| DEMO-PC-006 | PC, master, catégorie 2 | Barème IRD master et rappel 15 000 | 345 011 |
| DEMO-VAC-007 | VAC | Retenue exceptionnelle 2 500 | 144 000 |
| DEMO-VAC-008 | VAC | Sans élément variable | 146 500 |
| DEMO-VAC-009 | VAC | Un jour d’absence, retenue de test 5 000 | 141 500 |
| DEMO-VAC-010 | VAC | Rappel 15 000 | 161 500 |

Le premier PC présente 302 773 FCFA de gains, 42 103 FCFA de retenues,
soit 260 670 FCFA net, comme l'exemple fourni, avec une identité fictive.
Les autres montants utilisent les grilles et le moteur de calcul déjà présents.
Les données sont destinées au test fonctionnel, sans validation réglementaire
des taux ou des montants.

Les 10 présences et les éléments variables sont validés. Les bulletins ont
été calculés par le moteur réel et sont en attente de paiement ; la période
reste au statut `calculated` pour permettre de tester la validation.

## Référentiels réutilisés

- Année académique 2026-2027.
- Profils PC et VAC dans `statuts_enseignant`, comme attendu par les filtres de paie.
- IA Thiès et Dakar ; IEF Thiès Commune, Mbour 2 et Diamniadio.
  Diamniadio/Rufisque reste dans le rattachement Dakar existant du projet.
- Trois centres de formation et trois banques marqués Démonstration.
- Comptes fictifs `DEMO-COMPTE-*`, stockés dans la table administrative des comptes.
- Rubriques du paramétrage liées aux codes du calcul ; copie de leurs règles
  d'affichage dans chaque nouveau bulletin.

La colonne de code du catalogue administratif est limitée à 20 caractères.
`INDEMNITE_COMPENSATION` dépasse cette limite : son libellé calculé est conservé.
Le seeder ne modifie pas cette contrainte appartenant à Paramétrage.

Le détail de bulletin lit désormais le centre de formation et le compte
administratif actif principal quand les anciennes relations de paie sont vides.
Ce raccord est limité à `PayrollPageController` ; aucun modèle ou écran
Administration/Paramétrage n'a été modifié.

## Rejouer et vérifier

Depuis `sicoreBack` :

```powershell
& ..\.tools\php\php.exe artisan db:seed --class=PayrollDemoSeeder
```

Le seeder est réservé à APP_ENV=local et à la base `sicore_local`.
Si octobre 2026 existe déjà, il ne remplace rien et ne recrée pas les bulletins.
Il ne fait pas partie du seeder global : aucune donnée de démonstration n'est
injectée par défaut dans les autres installations.

Depuis le dossier `sicore`, serveurs démarrés :

```powershell
& .\.tools\php\php.exe verifier-local.php --demo
```

Cette commande vérifie la connexion, les deux listes et un bulletin PC/VAC.
Les identifiants directs 1 et 7 correspondent à cette installation initiale.
