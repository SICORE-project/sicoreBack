# Périodes de paie sans verrouillage d'ouverture/clôture

Cette règle remplace les étapes de clôture et les restrictions de statut
décrites dans les anciennes notes de cycle. Les valeurs open/calculated/
validated/closed conservées en base sont des métadonnées historiques ; elles
ne conditionnent plus la saisie, la validation des éléments, un recalcul
explicitement demandé, le paiement ou la consultation.

PayrollPeriod::isMutable retourne true pour tous ces statuts. Le moteur de
calcul ne rejette plus une période sur ce critère. Il protège en revanche tout
bulletin déjà payé, y compris lors d'un calcul national sans filtre. Les
contrôles de données administratives, montants, validation des saisies, doublons,
version et intégrité financière restent actifs.

La clôture automatique n'est plus planifiée. payroll:close-expired reste comme
commande de compatibilité sans effet pour les anciens superviseurs. Le lanceur
Windows du backend ne lance plus de surveillant de clôture. Aucune modification
massive des statuts historiques ni aucun recalcul des anciens bulletins n'est
effectué pour appliquer cette règle.

Le référentiel Code / Libellé et le calcul national lors de la création d'un
nouveau mois sont conservés. Une simple consultation ne recalcule jamais les
montants. Les règles de bénéficiaires et les dix échéances Tabaski restent
inchangées. Les règles des années académiques et des autres équipes ne sont pas
modifiées par ce changement limité aux périodes de paie.
