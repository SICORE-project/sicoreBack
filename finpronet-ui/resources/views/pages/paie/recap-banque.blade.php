@extends('layouts.app')

@section('title', 'SICORE - Etat recapitulatif par banque')
@section('body_attributes', 'class="app-body" data-module-page="paie-recap-banque"')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
@endpush

@section('content')
<main class="main-content">
    <header class="topbar" data-page-header></header>
    <section class="content-area" data-page-content></section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/app.js') }}" defer></script>
  <script src="{{ asset('assets/js/notifications.js') }}" defer></script>
  <script src="{{ asset('assets/js/pages.js') }}" defer></script>
@endpush

