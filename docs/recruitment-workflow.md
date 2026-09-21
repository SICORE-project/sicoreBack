# Lots de recrutement, OS et prise de service

L'import concerne exclusivement les **nouveaux enseignants vacataires**. Les
contractuels et fonctionnaires déjà présents restent dans la gestion des enseignants.
Le nouveau modèle CSV ne comporte plus de colonne `type_engagement` : le type
vacataire est attribué automatiquement. L'ancien modèle reste accepté uniquement
si toutes les lignes indiquent `vacataire` ; les autres types sont rejetés.

## Installation

Appliquer la migration `2026_09_22_000001_create_recruitment_workflow.php`,
puis exécuter `php artisan db:seed --class=RecruitmentPermissionSeeder` une fois
pour installer le catalogue de permissions et adapter les profils existants.
Les comptes sont ensuite créés normalement depuis l'administration, avec rôle,
permissions et structure. Aucun seeder par utilisateur n'est nécessaire.

Les profils DRH utilisent désormais les lots ; leurs anciennes permissions de
création, modification, suppression et activation manuelles sont retirées.
Le code des enseignants reste disponible pour les autres profils autorisés.

## Utilisation

1. DRH : ouvrir **Recrutement des vacataires** (`/personnel/recrutements`), puis
   **Importer une liste**, télécharger le modèle CSV, renseigner une référence
   de lot et sa date, vérifier l'aperçu puis confirmer. Tous les recrutés sont inactifs.
2. Joindre un OS PDF au lot, puis transmettre à la DAGE. Le lot devient figé.
   Les comptes DAGE actifs, rattachés à une structure DAGE active et disposant
   de `recruitment.read`, reçoivent une notification dans la page des lots.
3. IA : ouvrir le lot transmis, choisir le recruté et enregistrer la date
   effective, l'établissement et le certificat PDF. Seule une IA active du
   périmètre concerné peut effectuer cette action ; le recruté devient actif.
4. Une alerte à deux ans demande l'examen du dossier. La validation du passage
   vacataire → contractuel exige une décision PDF et une date d'effet valide.
   Elle ne modifie pas l'état actif/inactif.

Le CSV utilise `;` ou `,` et des dates AAAA-MM-JJ. Il peut provenir d'un fichier
déjà préparé : les colonnes sont reconnues indépendamment de leur ordre, avec
des intitulés tels que « Prénoms », « Nom » et « Date de naissance ». Le modèle
reste disponible par l'API mais n'est plus une étape obligatoire de la page.
Les champs IA/IEF/établissement sont leurs identifiants existants. Ils peuvent
rester vides à l'import national, mais le rattachement IA doit être renseigné
par un administrateur autorisé avant que cette IA puisse traiter le dossier.
Un matricule vide reçoit un identifiant **provisoire TMP**, pas un matricule officiel.
Limites : 5000 lignes, CSV de 5 Mo, PDF de 10 Mo. Les erreurs annulent tout l'import.

## Permissions

`recruitment.read`, `recruitment.import`, `recruitment.transmit`,
`recruitment.service`, `recruitment.transition` sont attribuables depuis
l'administration des rôles. Les permissions ne remplacent pas les contrôles
de périmètre ou le rattachement IA requis pour la prise de service.

## Alertes et documents

`php artisan recruitment:alerts` produit les notifications dues, sans doublons
et sans modifier les statuts. La commande est programmée quotidiennement dans
le planificateur Laravel. En local : `php artisan schedule:work`. Sur le serveur,
configurer l'exécution de `php artisan schedule:run` chaque minute.

Règle métier confirmée : les deux ans commencent à la **date effective de prise
de service**, enregistrée par l'IA. Sans prise de service, aucune échéance n'est
déclenchée. La date de recrutement et la date de saisie du certificat ne servent
pas de point de départ. Le passage à contractuel reste soumis à une décision.

Les pièces restent sur le disque privé `local`. Leur téléchargement exige une
authentification, la permission de lecture et le périmètre adéquat. L'historique
conserve import, OS successifs, transmission, prise de service et décision avec
auteur, date d'enregistrement et date d'effet. Aucune transition vers fonctionnaire
n'est automatisée ni supposée après deux ans.

## API Postman

Préfixe `/api/recruitment`, authentification Bearer et `Accept: application/json`.

- `GET /template` : modèle CSV.
- `POST /batches` multipart : `reference`, `recruited_at`, `file` ; `preview=1`
  pour contrôler et afficher les lignes sans rien enregistrer.
- `GET /batches`, `GET /batches/{id}` : lots visibles, membres et historique.
- `POST /batches/{id}/os` : `document` PDF.
- `POST /batches/{id}/transmit` : transmission et notifications.
- `POST /members/{id}/service` : `service_date`, `lieu_service_id`, `document` PDF.
- `POST /members/{id}/transition` : `engagement=contractuel`, `effective_date`, `document` PDF.
- `GET /documents/{event}` : justificatif d'un événement autorisé.
- `GET /notices`, `POST /notices/{id}/read` : notifications personnelles.

Les opérations répétées de transmission et de prise de service retournent 409.
Les listes hors périmètre restent vides ; les identifiants hors périmètre retournent 404.
