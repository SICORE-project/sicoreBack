# Dates et clôture des périodes de paie

> Note historique : les dates ne sont plus demandées dans le formulaire ; elles
> sont déterminées en interne. Voir `referentiel-periodes-main.md`.

La création exige date_debut et date_fin au format AAAA-MM-JJ. Les dates doivent
appartenir au même mois, correspondre au code de la période, être comprises dans
une année académique non clôturée et respecter début <= fin. La date de fin ne
peut pas être déjà passée lors de la création. Les dates définissent la période
mensuelle ; elles ne constituent pas une règle nouvelle de proratisation des salaires.

La migration 2026_11_16_120000_add_dates_to_periode_de_paies ajoute deux dates
facultatives pour compatibilité historique et un lien vers payroll_periods.
Seules les périodes créées avec ces dates et ce lien sont automatiquement closes.
Les anciennes références restent inchangées, avec les dates de leur cycle
affichées à titre historique et une clôture manuelle. Les dates d'un cycle calculé
ne sont pas modifiables depuis l'édition du libellé. Une référence liée à un cycle
ne peut pas être supprimée, afin de préserver la programmation et l'historique.

La date de fin est incluse : clôture à partir de 00:00 le lendemain, selon le
fuseau de l'application Laravel. payroll:close-expired réutilise les contrôles
de validation, checksum et clôture existants. L'auteur du calcul est conservé
pour les écritures d'audit ; la requête d'audit identifie le chemin système.
Les erreurs sont journalisées et la période n'est pas marquée close en cas
d'échec. L'opération peut être répétée sans rouvrir ou doubler les traitements.

La commande est planifiée chaque minute dans routes/console.php. En hébergement,
le planificateur Laravel doit être actif (cron lançant artisan schedule:run chaque
minute ou service artisan schedule:work). Sans processus actif, aucune tâche ne
peut s'exécuter à minuit. La prochaine création/ouverture tente aussi de clôturer
les périodes échues avant de contrôler la période active.

En local Windows, demarrer-back.ps1 démarre un surveillant masqué avec le serveur,
qui exécute uniquement payroll:close-expired toutes les 60 secondes. Le surveillant
s'arrête avec le serveur. Redémarrer le script backend après cette mise à jour.
Le fichier racine surveiller-periodes-paie.ps1 doit être présent à côté du lanceur.
Autre possibilité dans un terminal backend : php artisan schedule:work.

La liste affiche les dates, le statut explicite « Ouverte — paie calculée » /
« Ouverte — paie validée » / « Clôturée », ainsi que l'horodatage de clôture.
Les bulletins historiques restent consultables.
