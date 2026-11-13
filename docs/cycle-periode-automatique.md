# Ouverture et calcul automatiques des périodes

Le point d'entrée est Paramétrage → Périodes de paie. La création du catalogue et
le calcul des enseignants PC/VAC actifs de toutes les IA sont dans une même
transaction. Les dossiers administratifs et le moteur financier existant sont
réutilisés ; aucune valeur manquante n'est inventée. Un dossier incomplet ou une
rubrique invalide annule l'ensemble de l'ouverture, y compris le nouvel item du
catalogue. Le code AAAA-MM permet d'éviter toute ambiguïté d'année académique.

PayrollPeriodLifecycle sérialise les ouvertures et les clôtures via les lignes
des années académiques verrouillées. Toute période open, calculated ou validated
bloque une ouverture supplémentaire. Le calcul terminé donne le statut calculated
(la période est en cours, mais ses calculs existent déjà). Le bouton de clôture
du formulaire valide l'intégrité du calcul avec le service existant, puis clôture
le cycle. Un cycle historique vide peut être clôturé sans inventer de bulletin ;
une période avec des saisies non traitées ne peut pas être ainsi clôturée.

Les anciennes références du catalogue restent visibles. « Ouvrir et calculer »
sert à utiliser un mois déjà paramétré pour une année non encore traitée. Un cycle
déjà traité/clôturé ne peut pas être rouvert. Les anciens cycles non clôturés
doivent être régularisés avant une nouvelle ouverture, sans clôture automatique.

Les lectures du catalogue n'ouvrent plus de période. Toutes les périodes
existantes restent disponibles pour l'historique, même si leur référence ou leur
année académique n'est plus active. Les années clôturées sont disponibles dans
les filtres de consultation des états et bulletins. Les montants historiques
ne sont pas recalculés lors d'une consultation.

L'action API generate-payslips, son formulaire, ses boutons et son service de
sélection manuelle ont été retirés. Le moteur PayrollCalculationService est
conservé pour le calcul interne. Les fichiers du rapport EtatSalaireReport et
ses vues/styles ne sont pas modifiés.

Pas de migration ni de seeder pour ce changement. L'ouverture est synchrone et
atomique ; le client de création des périodes autorise jusqu'à 300 secondes pour
recevoir le résultat national. Pour un déploiement avec de grands effectifs, les
délais PHP/serveur doivent aussi permettre de terminer le traitement.

Vérification : tests PayrollPeriodLifecycleTest, PayrollPeriodCatalogueTest,
PeriodePaieApiTest, PayrollPayslipPresentationTest et tests frontend de
consultation/affichage. Une simulation transactionnelle sur sicore_local a fermé
les cycles existants puis calculé un nouveau mois pour 12 enseignants avant
annulation complète, afin de conserver toutes les données utilisateur.
