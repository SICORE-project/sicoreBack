# Espace personnel enseignant — ADM-INT-004

Le rôle `enseignant` utilise exclusivement son espace personnel. Le menu contient Tableau de bord, Mes informations et Mes bulletins de salaire. Les routes de gestion sont refusées côté frontend et API, même si des permissions de gestion ont été attribuées au rôle.

## Rattachement

Créer le dossier enseignant dans la gestion du personnel, puis saisir son matricule exact dans le formulaire de création/modification du compte utilisateur. Le serveur renseigne `users.enseignant_id`. Un dossier déjà associé à un autre compte est refusé. Aucun rapprochement automatique par nom ou email n'est effectué. Un compte sans dossier associé reste fermé à la consultation des informations et bulletins.

## API personnelle

- `GET /api/enseignant/dossier` : informations du dossier associé au compte connecté.
- `GET /api/enseignant/bulletins?annee=2026&periode_id=1&page=1` : bulletins personnels paginés et choix de filtres issus exclusivement de ces bulletins.
- `GET /api/enseignant/bulletins/{id}/pdf` : PDF en consultation.
- `GET /api/enseignant/bulletins/{id}/pdf?download=1` : téléchargement PDF.

Les bulletins sont disponibles lorsque leur période est validée ou clôturée. Les périodes en cours de calcul ne sont pas publiées dans cet espace. L'identifiant de l'enseignant est toujours déterminé par le compte connecté, jamais par un paramètre client. Un bulletin d'un autre enseignant retourne 404. Les réponses personnelles et PDF sont privées et non mises en cache. Les consultations et téléchargements PDF sont journalisés dans `payroll_audit_logs`.

Le statut professionnel provient de `type_engagement`. Les valeurs absentes sont indiquées comme non renseignées. L'indice, nullable, est affiché uniquement pour un fonctionnaire et peut être renseigné par l'API de gestion du personnel. Les PDF sont générés en mémoire par DomPDF, sans dépôt dans un répertoire public.

## Vérification

Backend : `php artisan test --compact tests/Feature/TeacherPersonalAccessTest.php`.

Frontend (sicoreFront) : `php artisan test --compact tests/Feature/TeacherPersonalWorkspaceTest.php`.

La migration `2026_09_23_020000_add_teacher_indice` ajoute l'indice au dossier enseignant. DomPDF doit être installé à partir du `composer.lock` existant.
