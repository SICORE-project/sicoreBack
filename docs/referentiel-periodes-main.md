# Référentiel des périodes : retour à la présentation main

Cette évolution remplace les étapes manuelles décrites dans les anciennes notes
de cycle de paie et de dates. L'interface Paramétrage reprend la version main :
Code et Libellé uniquement, puis liste Code / Libellé / Actions. Les statistiques
du référentiel main sont conservées. Les boutons techniques, les dates et le
tableau de cycles spécifiques à notre branche ne sont plus exposés ici.

La création conserve un seul branchement vers PayrollPeriodLifecycle, dans une
transaction. Le code/libellé identifie le mois dans l'année académique active ;
les dates du premier au dernier jour sont déterminées et stockées en interne.
Le moteur national et les échéances Tabaski sont exécutés sans bouton de calcul.
En cas d'erreur de données, le calcul et la nouvelle référence sont annulés et
le formulaire simple affiche l'erreur. La création d'un alias pour un mois déjà
calculé retrouve son cycle sans recalculer ni rouvrir son historique.

Comme dans le référentiel main, les mois peuvent être ajoutés dans n'importe
quel ordre. Enregistrer un mois futur ne clôture pas prématurément un autre mois.
La commande payroll:close-expired clôture les cycles après leur date de fin
inclusive, en conservant les contrôles d'intégrité existants. Elle se base sur
payroll_periods.end_date, même si la référence du catalogue a été supprimée.
Le planificateur/contrôle local doit rester actif comme précédemment.

La liste des périodes de consultation provient de l'historique enregistré et
ne perd pas ses anciennes périodes après modification/suppression du catalogue.
Les sélecteurs Blade et les formulaires JS affichent seulement le libellé du mois
et son année ; les statuts techniques restent disponibles dans l'API pour les
contrôles internes et l'audit, sans suffixe « Ouverte / Calculée / Clôturée ».
Le code financier de l'état des salaires et la présentation des bulletins ne sont
pas modifiés. La seule modification de sa vue de filtre retire le suffixe statut.

Les colonnes de dates précédemment ajoutées sont conservées : aucune suppression
de données, aucun retour arrière de migration ni réécriture des bulletins.
