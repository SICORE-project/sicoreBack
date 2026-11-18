# Avance et retenue Tabaski programmées

L'application collective d'une avance enregistre immédiatement une liste fixe
de bénéficiaires PC/VAC actifs des IA sélectionnées. Le formulaire demande le
statut, les IA et un premier mois AAAA-MM postérieur à la dernière période
enregistrée. Il ne demande plus dix périodes préexistantes, une année académique
ni un montant libre : 100 000 FCFA par avance, 10 000 FCFA par retenue.

L'IEF facultative est limitée aux IA cochées. Un matricule exact facultatif permet
de traiter un seul formateur ; sans matricule, l'opération reste collective dans
le périmètre IA/IEF choisi. Les filtres se cumulent : un matricule hors de l'IEF
sélectionnée ne peut pas élargir la sélection. Pour les retenues, les suggestions
et le filtrage utilisent les bénéficiaires et rattachements figés lors de
l'avance ; un matricule nouvellement créé ne permet pas d'inventer une dette.

La retenue est activée séparément, uniquement sur des avances déjà attribuées
et pas encore activées dans la sélection. Elle ne sélectionne jamais la liste
actuelle de tous les enseignants. Le statut/IA mémorisés lors de l'avance servent
à sélectionner ses bénéficiaires. Les enseignants ajoutés après l'attribution
ne bénéficient pas de cette avance et ne sont pas débités pour son remboursement.

PayrollTabaskiSchedule stocke les attributions dans payroll_tabaski_allocations
et les échéances matérialisées dans payroll_tabaski_installments. Lors de
l'ouverture d'un mois, PayrollPeriodLifecycle appelle materialize dans sa propre
transaction, avant le calcul national. Une avance non encore versée est ajoutée
dans payroll_elements, code TABASKI_AVANCE, gain de 100 000 FCFA. Son mois de
versement est enregistré. Une retenue activée commence au plus tôt le mois
suivant ce versement et jamais avant le premier mois demandé. Le même bulletin
ne comporte pas à la fois le versement et le remboursement de cette avance.

Chaque ouverture ultérieure ajoute une retenue TABASKI_RETENUE de 10 000 FCFA
pour le bénéficiaire, jusqu'à dix échéances effectivement prises en compte. Les
identifiants uniques protègent les séquences/mois et les doubles clics ; une
nouvelle avance n'est pas ajoutée si une précédente reste à rembourser. Un échec
de calcul annule à la fois les éléments, les échéances et le marquage du versement.
Une opération répétée ne double pas une échéance. Les anciens bulletins ne sont
pas réécrits. Une nouvelle attribution après remboursement complet constitue une
nouvelle campagne explicite, pas une prolongation des dix retenues précédentes.

Le moteur existant reprend les éléments validés non exemptés dans les lignes
du bulletin et ses totaux. Les montants ne sont pas lus depuis une rubrique
globale pour attribuer une avance à des non-bénéficiaires. Les formulaires
d'éléments variables ne permettent plus de saisir manuellement les deux codes
réservés Tabaski. Le rapport affiche le versement, le compteur /10 et le reste.

Le calendrier ne dépend pas de l'année académique d'origine : les échéances
continuent dans l'année suivante. Si aucun bulletin n'est calculé pour un mois
ou si le bénéficiaire est inactif et absent du calcul national, aucune échéance
n'est inventée : le compteur reprend au prochain bulletin éligible, sans doubler
la mensualité de 10 000 FCFA. Le solde n'est pas effacé par la clôture d'un mois.

L'historique antérieur reste visible, hors échéancier. Il n'est pas converti
automatiquement : une ancienne retenue isolée ne prouve pas qu'une avance a été
versée. Aucun montant ni remboursement historique n'est fabriqué par la migration.

Migration : 2026_11_16_150000_create_payroll_tabaski_schedules.php.
Tests : PayrollTabaskiScheduleTest, PayrollTabaskiValidationTest et
PayrollTabaskiIntegrationTest (frontend). Simulation locale en transaction :
avance en janvier, dix retenues de février à novembre, aucune en décembre,
nouvel enseignant exclu ; annulation complète après vérification des vrais bulletins.
