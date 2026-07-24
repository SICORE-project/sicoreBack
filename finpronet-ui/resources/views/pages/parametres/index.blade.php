@extends('layouts.app')

@section('title', 'SICORE - Param&eacute;trage')
@section('body_attributes', 'class="app-body"')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
@endpush

@section('content')
<main class="main-content">
    <header class="topbar">
      <div class="page-title-wrap">
        <button class="mobile-menu-btn" type="button" data-sidebar-toggle aria-label="Ouvrir le menu">&#9776;</button>
        <span class="title-icon" aria-hidden="true"><i class="fa-solid fa-gears"></i></span>
        <div>
          <h1>Param&eacute;trage</h1>
          <p>Gestion des param&egrave;tres syst&egrave;me</p>
        </div>
      </div>
      <div class="search-wrap">
        <label class="sr-only" for="settingsSearch">Rechercher un param&egrave;tre</label>
        <input class="search-input" id="settingsSearch" type="search" placeholder="Rechercher un param&egrave;tre..." data-table-filter="#settingsTable">
      </div>
    </header>

    <section class="content-area">
      <div class="actions-row">
        <div>
          <p class="breadcrumb">Administration &gt; Param&eacute;trage &gt; R&eacute;f&eacute;rentiels</p>
        </div>
        <div class="actions-group">
          <button class="btn-primary" type="button">+ Nouveau param&egrave;tre</button>
          <button class="btn-secondary" type="button">Importer</button>
          <button class="btn-secondary" type="button">Exporter</button>
        </div>
      </div>

      <section class="table-card">
        <div class="table-responsive">
          <table class="table" id="settingsTable">
            <thead>
              <tr>
                <th>Code</th>
                <th>Nom</th>
                <th>Statut</th>
                <th class="actions-cell">Actions</th>
              </tr>
            </thead>
            <tbody>
              <tr id="ia">
                <td>IA</td>
                <td>Inspection d&rsquo;Acad&eacute;mie</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="diplomes">
                <td>DIP</td>
                <td>Dipl&ocirc;mes</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="corps">
                <td>COR</td>
                <td>Corps</td>
                <td><span class="badge badge-pending">En attente</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="categories">
                <td>CAT</td>
                <td>Cat&eacute;gories</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="institutions-financieres">
                <td>IF</td>
                <td>Institutions financi&egrave;res</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="discipline">
                <td>DIS</td>
                <td>Discipline</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="syndicats">
                <td>SYN</td>
                <td>Syndicats</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="annee-academique">
                <td>AA</td>
                <td>Ann&eacute;e acad&eacute;mique</td>
                <td><span class="badge badge-pending">En attente</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="periode-paie">
                <td>PP</td>
                <td>P&eacute;riode de paie</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="rubrique-paie">
                <td>RP</td>
                <td>Rubrique de paie</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="rubriques-corps">
                <td>RC</td>
                <td>Rubriques par corps</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
              <tr id="lieu-service">
                <td>LS</td>
                <td>Lieu de service</td>
                <td><span class="badge badge-active">Actif</span></td>
                <td class="actions-cell"><button class="icon-action" title="Voir">&#128065;</button><button class="icon-action" title="Modifier">&#9998;</button><button class="icon-action" title="Supprimer">&#128465;</button></td>
              </tr>
            </tbody>
          </table>
        </div>
        <p class="empty-message">Aucun param&egrave;tre trouv&eacute;.</p>
        <div class="pagination">
          <button class="page-btn" type="button">&#8592;</button>
          <button class="page-btn active" type="button" data-page-number>1</button>
          <button class="page-btn" type="button" data-page-number>2</button>
          <button class="page-btn" type="button">&#8594;</button>
        </div>
      </section>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/app.js') }}" defer></script>
  <script src="{{ asset('assets/js/notifications.js') }}" defer></script>
@endpush

