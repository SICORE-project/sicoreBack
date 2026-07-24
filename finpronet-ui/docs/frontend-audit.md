# Audit frontend SICORE

Date: 2026-07-09
Projet local audite: `finpronet-ui`
Nom cible: `SICORE`
Perimetre: frontend uniquement, sans backend metier ni base de donnees metier.

## Verifications systeme et Git

- PHP detecte: `PHP 8.2.12 (cli)` via `C:\xampp\php\php.exe`.
- Fichier PHP charge: `C:\xampp\php\php.ini`.
- Composer detecte: `Composer 2.7.4`, utilise PHP `8.2.12`.
- Node detecte: `v24.16.0`.
- NPM detecte: `11.13.0`.
- Branche initiale auditee: `main`.
- Remote Git: `origin https://github.com/BayeSaliou1995/sicore.git`.
- Dernier commit avant migration: `8cb2552 Initialisation du projet SICORE`.
- Arbre Git initial: propre.

## Structure actuelle

```text
.
|-- *.html
|-- README.md
|-- sicore.zip
|-- assets/
|   |-- css/
|   |   |-- app.css
|   |   |-- responsive.css
|   |   `-- style.css
|   |-- icons/
|   |   `-- .gitkeep
|   |-- images/
|   |   |-- flag-senegal.svg
|   |   |-- image-fcfa.png
|   |   `-- logo.svg
|   `-- js/
|       |-- app.js
|       |-- charts.js
|       |-- education-structures.js
|       |-- form-wizard.js
|       |-- notifications.js
|       `-- pages.js
```

## Inventaire HTML

39 fichiers HTML ont ete detectes a la racine:

- `index.html`
- `dashboard.html`
- `enseignant-dashboard.html`
- `enseignant-form.html`
- `ief.html`
- `parametres.html`
- `paie-etats-presence.html`
- `paie-avance-tabaski.html`
- `paie-retenue-tabaski.html`
- `paie-retenues-rappel.html`
- `paie-exemptions.html`
- `paie-travaux-periodiques.html`
- `paie-recap-banque.html`
- `paie-cotisations-sociales.html`
- `paie-etat-salaires.html`
- `paie-elements-saisie-dashboard.html`
- `paie-generee-ief.html`
- `paie-fermeture-periode.html`
- `paie-edition-salaires-banque.html`
- `paie-bulletins.html`
- `paie-effectifs-corps.html`
- `paie-non-generee.html`
- `paie-sommes-percues.html`
- `credit-delegation.html`
- `credit-edition-delegations.html`
- `credit-edition-engagements.html`
- `indemnites-convocations.html`
- `indemnites-services-faits.html`
- `indemnites-pieces-justificatives.html`
- `indemnites-accuses-reception.html`
- `indemnites-calcul.html`
- `indemnites-frais-deplacement.html`
- `indemnites-etats-paie.html`
- `bourses-enregistrer-demande.html`
- `bourses-valider-dossier.html`
- `bourses-attribuer-aide.html`
- `utilisateurs.html`
- `profils-roles.html`
- `permissions.html`

## Styles CSS

- `assets/css/style.css`: styles de base, ecran de connexion, composants generaux et toasts.
- `assets/css/app.css`: shell applicatif, sidebar, topbar, tableaux, cartes, formulaires, workflows, modales, graphiques.
- `assets/css/responsive.css`: breakpoints `1200`, `992`, `768`, `560`, `390`.

Aucun framework CSS externe local n'a ete detecte. Le design repose sur CSS natif.

## JavaScript

- `assets/js/app.js`
  - Injecte Font Awesome via CDN.
  - Injecte le sidebar pour les pages avec `body.app-body`.
  - Gere sidebar desktop/mobile, overlay mobile, sous-menus, menu actif et etat `localStorage`.
  - Gere filtres de tableaux, pagination visuelle, validation frontend simple, confirmations, toasts et calculs de demonstration.
  - Expose `window.SICOREApp`.
- `assets/js/notifications.js`
  - Cree et affiche les notifications visuelles.
- `assets/js/charts.js`
  - Dessine les graphiques sur canvas pour le dashboard principal et le dashboard enseignants.
- `assets/js/pages.js`
  - Contient les donnees et templates frontend de nombreuses pages metier.
  - Remplit les pages qui portent `data-module-page`.
- `assets/js/education-structures.js`
  - Alimente les listes IA/IEF du formulaire enseignant.
- `assets/js/form-wizard.js`
  - Gere le workflow "Nouvel enseignant" en 3 etapes avec validation frontend.

## Images, icones et polices

- Images locales:
  - `assets/images/flag-senegal.svg`
  - `assets/images/image-fcfa.png`
  - `assets/images/logo.svg`
- Dossier icones local:
  - `assets/icons/.gitkeep`
- Bibliotheque d'icones externe:
  - Font Awesome `6.5.2`, chargee dynamiquement depuis `https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css`.
- Aucune police locale `.ttf`, `.otf`, `.woff` ou `.woff2` detectee.

## CDN et dependances externes

- CDN detecte: Font Awesome via `assets/js/app.js`.
- Aucun package NPM existant.
- Aucun `composer.json` existant avant migration.
- Aucune dependance backend existante.

## Composants partages

- Sidebar: genere dans `assets/js/app.js` par `getSidebarMarkup()` et `buildSidebarMenu()`.
- Header/topbar: present dans les pages dediees et genere par `pages.js` pour les pages `data-module-page`.
- Footer: aucun footer commun detecte.
- Notifications: gerees par `notifications.js`.
- Modale de confirmation: generee par `app.js`.
- Pagination visuelle: classe `.pagination`, boutons `.page-btn`, geree par `app.js`.
- Tableaux: classe `.table`, filtre par `data-table-filter`.
- Formulaire enseignant: page dediee `enseignant-form.html`, scripts `education-structures.js` et `form-wizard.js`.
- Graphiques canvas: `dashboard.html`, `enseignant-dashboard.html`, script `charts.js`.

## Pages dynamiques via `pages.js`

Les pages suivantes ont un DOM minimal et dependent de `assets/js/pages.js` avec `data-module-page`:

- Gestion de la paie: `paie-*.html`, `credit-*.html`.
- Gestion des indemnites: `indemnites-*.html`.
- Bourses et aides: `bourses-*.html`.
- Gestion utilisateur: `utilisateurs.html`, `profils-roles.html`, `permissions.html`.

Ces pages doivent conserver leur attribut `data-module-page`, leurs zones `data-page-header` et `data-page-content`, ainsi que le chargement de `pages.js`.

## Pages dediees

- `index.html`: ecran de connexion frontend, validation locale seulement.
- `dashboard.html`: dashboard principal avec graphiques.
- `enseignant-dashboard.html`: dashboard enseignants avec graphique et tableau filtrable.
- `enseignant-form.html`: workflow Nouvel enseignant, 3 panneaux et validation frontend.
- `parametres.html`: referentiels systeme avec ancres internes.
- `ief.html`: page IEF.

## Liens HTML directs detectes

- `index.html` contient un lien vers `dashboard.html`.
- `enseignant-form.html` contient un lien d'annulation vers `enseignant-dashboard.html`.
- `assets/js/app.js` contient tous les liens de navigation du sidebar en `.html`.

Ces liens devront etre remplaces par des URLs Laravel propres ou par une table de routes frontend cote JS. Les IDs, classes et attributs `data-*` doivent etre conserves.

## Scripts inline et styles inline

- Aucun script inline HTML significatif detecte.
- `assets/js/pages.js` genere un style inline controle pour les barres de mini-graphique: `style="height:...px"`.
- Les styles inline generes font partie du rendu existant et ne doivent pas etre supprimes dans cette migration.

## Risques de migration

- `app.js` determine la page active avec `window.location.pathname.split("/").pop()` et compare a des noms `.html`. Il faudra l'adapter aux URLs Laravel sans casser l'ancien comportement.
- `app.js` genere le sidebar avec des chemins `assets/images/...` et `*.html`. Ces chemins doivent fonctionner sous Laravel.
- `pages.js` depend de `data-module-page`; une erreur sur le slug viderait la page metier.
- Les pages profondes Laravel doivent supporter le rafraichissement direct sans 404.
- Les assets devront etre places sous `public/assets`.
- Font Awesome reste une dependance CDN; une coupure reseau peut affecter les icones, comme dans la version statique.
- Les syntaxes Blade `{{ }}` ne sont pas presentes dans les fichiers actuels; les `@` detectes sont surtout CSS media/keyframes et regex email.
- La migration ne doit pas introduire de base de donnees, d'authentification reelle, de CRUD metier ou de starter kit.

## Recommandations de migration

- Installer Laravel avec contrainte `12.*`.
- Conserver les assets legacy dans `public/assets`.
- Creer un layout Blade qui preserve les memes `link` et `script` selon les pages.
- Convertir les pages dediees en vues Blade en conservant le DOM.
- Pour les pages `data-module-page`, utiliser un template Blade generique ou des vues minces qui conservent exactement la structure actuelle.
- Adapter `assets/js/app.js` pour naviguer vers les routes Laravel et pour determiner la page active a partir de slugs.
- Ajouter `.gitignore` avant toute installation locale de `vendor` ou `node_modules`.
- Documenter chaque page dans `docs/frontend-page-map.md`.
