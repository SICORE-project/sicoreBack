# Validation des enseignants proposes par une IA

Le gestionnaire IA utilise **Enseignants > Ajouter un enseignant**. Le formulaire habituel est reutilise, avec les references de son IA. L'envoi cree une ligne dans `teacher_submissions`, sans creer de ligne dans `enseignants` ni dans les tables de paie.

Le gestionnaire suit les dossiers via **Mes soumissions**. Le super administrateur consulte **Enseignants > Dossiers IA a valider**, ouvre le dossier et le valide. La creation definitive reprend `EnseignantService`, avec les comptes bancaires et rattachements existants. La validation et la creation sont atomiques ; une deuxieme validation est sans effet.

Les controles de formulaire et de hierarchie sont ceux de `StoreEnseignantRequest`. La soumission impose l'IA du compte connecte. La validation est reservee au role `super_admin`, et recontrole les identifiants uniques avant toute creation. L'auteur, le validateur, la date de validation et l'enseignant resultant sont conserves.

Migration : `2026_11_08_120000_create_teacher_submissions_table.php`. Les enseignants deja enregistres ne sont pas modifies. Aucun nouveau droit de modification/suppression des enseignants definitifs n'est accorde au gestionnaire IA.

Tests : `TeacherSubmissionWorkflowTest`, `TeacherSubmissionReviewTest`, tests de soumission dans `IaWorkspaceTest` et controle des routes dans `IaAccessTest`.
