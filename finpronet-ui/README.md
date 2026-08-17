# SICORE UI

Interface HTML5, CSS3 et JavaScript pur pour SICORE.

SICORE signifie : Syst&egrave;me Int&eacute;gr&eacute; des COrps &Eacute;mergents.

## Pages principales

- `index.html` : connexion SICORE.
- `dashboard.html` : tableau de bord.
- `enseignant-dashboard.html` : suivi des enseignants.
- `enseignant-form.html` : workflow progressif en 3 etapes.
- `parametres.html` et `ief.html` : pages d'administration existantes.

## Modules

- Gestion de la paie.
- Gestion des indemnit&eacute;s.
- Gestion des bourses et aides.
- Param&eacute;trage.
- Gestion Utilisateur.

La navigation commune est inject&eacute;e par `assets/js/app.js`, ce qui permet de garder une sidebar coh&eacute;rente sur toutes les pages. Les pages m&eacute;tier utilisent `assets/js/pages.js` pour afficher un contenu propre avec cartes, filtres, tableaux, badges, pagination et actions.

## JavaScript

- `assets/js/app.js` : sidebar desktop/mobile, localStorage, menu actif, recherche de tableaux, confirmations, toasts.
- `assets/js/notifications.js` : notifications succes, erreur, avertissement et information.
- `assets/js/form-wizard.js` : formulaire enseignant progressif avec validation.
- `assets/js/pages.js` : generation des pages metier.
- `assets/js/charts.js` : graphiques du dashboard.

## Couleur principale

La couleur institutionnelle principale reste `#166534`.
# SICORE Front BFF

Le navigateur n'accède jamais au token API : l'authentification est relayée au backend par Laravel et le token Sanctum reste dans la session serveur. Configurer `SICORE_API_URL` avec l'URL du backend.

En production, `APP_ENV=production`, `APP_DEBUG=false`, HTTPS et des cookies de session `secure`/`httpOnly` sont obligatoires.
