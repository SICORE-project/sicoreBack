# Avances et retenues Tabaski

Le formulaire propose les douze mois de janvier à décembre et une année. Le mois choisi est le mois du bulletin qui reçoit l'avance de 100 000 FCFA. Il peut précéder des périodes déjà présentes dans la base : aucune condition liée au dernier mois calculé, à l'ouverture ou à la fermeture ne le bloque.

Les bénéficiaires PC/VAC sont figés à l'attribution, selon les IA, l'IEF facultative et le matricule facultatif. Les enseignants ajoutés ensuite ne reçoivent aucune dette liée à cette avance.

Si l'état des salaires existe, PayrollTabaskiBulletinUpdater ajoute uniquement la ligne Tabaski aux bulletins concernés et met à jour leurs totaux et contrôles d'intégrité. Les autres lignes et leurs paramètres mémorisés restent identiques. Une cible déjà payée ou contenant une ligne Tabaski de même type annule l'opération entière, sans modification partielle.

Si l'état du mois n'existe pas encore, l'attribution reste en attente pour ce mois précis. PayrollCalculationService appelle PayrollTabaskiSchedule::materialize lors du calcul de l'état. Aucun bulletin n'est créé par l'application collective elle-même. La consultation des bulletins demeure limitée aux enseignants présents dans l'état des salaires.

La retenue est activée séparément pour les seuls bénéficiaires de l'avance. Elle commence au plus tôt le mois suivant le versement et couvre dix mois calendaires consécutifs, à 10 000 FCFA par mois. Les échéances continuent au changement d'année. Un mois sans état des salaires ne fabrique pas de bulletin et ne décale pas les échéances : sa retenue sera prise en compte si son état est calculé ultérieurement. Le solde restant est conservé.

Tables : payroll_tabaski_allocations (bénéficiaires et calendrier), payroll_tabaski_installments (échéances), payroll_elements (éléments), payroll_payslips et payroll_payslip_lines (bulletins). Les contraintes uniques et les transactions empêchent les doubles attributions et les doubles échéances. L'historique antérieur reste séparé et n'est pas converti automatiquement.

Les filtres des bulletins présentent janvier à décembre ; les mois sans état disponible sont désactivés. Les années et dates internes restent nécessaires pour distinguer deux mois identiques d'années différentes. Le CRUD des périodes de Paramétrage et le code de l'état des salaires restent conservés.

Tests : PayrollTabaskiScheduleTest, PayrollTabaskiValidationTest, PayrollTabaskiIntegrationTest, PayrollBulletinConsultationTest.

## Contrôle des retenues

Une avance simplement programmée ne permet plus d'activer le remboursement. La ligne TABASKI_AVANCE du montant attribué doit déjà exister sur le bulletin du bénéficiaire pour la période du versement. Le serveur et les suggestions appliquent ce contrôle. Le calcul exclut toute retenue sans attribution reçue et échéance correspondante. Les anciennes lignes hors échéancier ne sont plus affichées dans les rapports ; les données et les bulletins historiques sont conservés.
