# Cartographie des pages frontend SICORE

Date: 2026-07-09

Statuts initiaux:

- Migration: `A faire`
- Validation visuelle: `A faire`
- JavaScript: `A faire`

| Ancien fichier HTML | Nouvelle URL Laravel | Route | Vue Blade cible | Migration | Validation visuelle | JavaScript |
| --- | --- | --- | --- | --- | --- | --- |
| `index.html` | `/` | `login` | `resources/views/pages/auth/login.blade.php` | A faire | A faire | A faire |
| `dashboard.html` | `/dashboard` | `dashboard` | `resources/views/pages/dashboard/index.blade.php` | A faire | A faire | A faire |
| `enseignant-dashboard.html` | `/enseignants` | `enseignants.index` | `resources/views/pages/enseignants/index.blade.php` | A faire | A faire | A faire |
| `enseignant-form.html` | `/enseignants/nouveau` | `enseignants.create` | `resources/views/pages/enseignants/create.blade.php` | A faire | A faire | A faire |
| `parametres.html` | `/parametres` | `parametres.index` | `resources/views/pages/parametres/index.blade.php` | A faire | A faire | A faire |
| `ief.html` | `/parametres/ief` | `parametres.ief` | `resources/views/pages/parametres/ief.blade.php` | A faire | A faire | A faire |
| `paie-etats-presence.html` | `/paie/etats-presence` | `paie.etats-presence` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-avance-tabaski.html` | `/paie/avance-tabaski` | `paie.avance-tabaski` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-retenue-tabaski.html` | `/paie/retenue-tabaski` | `paie.retenue-tabaski` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-retenues-rappel.html` | `/paie/retenues-rappel` | `paie.retenues-rappel` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-exemptions.html` | `/paie/exemptions` | `paie.exemptions` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-travaux-periodiques.html` | `/paie/travaux-periodiques` | `paie.travaux-periodiques` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-recap-banque.html` | `/paie/recap-banque` | `paie.recap-banque` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-cotisations-sociales.html` | `/paie/cotisations-sociales` | `paie.cotisations-sociales` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-etat-salaires.html` | `/paie/etat-salaires` | `paie.etat-salaires` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-elements-saisie-dashboard.html` | `/paie/elements-saisie-dashboard` | `paie.elements-saisie-dashboard` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-generee-ief.html` | `/paie/generee-ief` | `paie.generee-ief` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-fermeture-periode.html` | `/paie/fermeture-periode` | `paie.fermeture-periode` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-edition-salaires-banque.html` | `/paie/edition-salaires-banque` | `paie.edition-salaires-banque` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-bulletins.html` | `/paie/bulletins` | `paie.bulletins` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-effectifs-corps.html` | `/paie/effectifs-corps` | `paie.effectifs-corps` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-non-generee.html` | `/paie/non-generee` | `paie.non-generee` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `paie-sommes-percues.html` | `/paie/sommes-percues` | `paie.sommes-percues` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `credit-delegation.html` | `/credits/delegation` | `credits.delegation` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `credit-edition-delegations.html` | `/credits/edition-delegations` | `credits.edition-delegations` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `credit-edition-engagements.html` | `/credits/edition-engagements` | `credits.edition-engagements` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-convocations.html` | `/indemnites/convocations` | `indemnites.convocations` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-services-faits.html` | `/indemnites/services-faits` | `indemnites.services-faits` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-pieces-justificatives.html` | `/indemnites/pieces-justificatives` | `indemnites.pieces-justificatives` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-accuses-reception.html` | `/indemnites/accuses-reception` | `indemnites.accuses-reception` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-calcul.html` | `/indemnites/calcul` | `indemnites.calcul` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-frais-deplacement.html` | `/indemnites/frais-deplacement` | `indemnites.frais-deplacement` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `indemnites-etats-paie.html` | `/indemnites/etats-paie` | `indemnites.etats-paie` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `bourses-enregistrer-demande.html` | `/bourses/enregistrer-demande` | `bourses.enregistrer-demande` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `bourses-valider-dossier.html` | `/bourses/valider-dossier` | `bourses.valider-dossier` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `bourses-attribuer-aide.html` | `/bourses/attribuer-aide` | `bourses.attribuer-aide` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `utilisateurs.html` | `/utilisateurs` | `utilisateurs.index` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `profils-roles.html` | `/utilisateurs/profils-roles` | `utilisateurs.profils-roles` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |
| `permissions.html` | `/utilisateurs/permissions` | `utilisateurs.permissions` | `resources/views/pages/modules/show.blade.php` | A faire | A faire | A faire |

## Compatibilite conseillee

Des redirections temporaires pourront etre ajoutees pour les anciennes URLs `.html`, par exemple `dashboard.html` vers `route('dashboard')`, afin d'eviter une rupture si un favori ou un lien externe pointe encore vers l'ancien fichier.
