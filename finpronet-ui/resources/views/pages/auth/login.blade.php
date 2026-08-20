@extends('layouts.app')

@section('title', 'SICORE - Connexion')
@section('body_attributes', '')

@push('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
@endpush

@section('content')
<main class="login-wrapper">
    <section class="login-card" aria-label="Connexion SICORE">
      <div class="login-left">
        <div class="login-left-content">
          <div class="republic-block">
            <img class="flag-image" src="{{ asset('assets/images/flag-senegal.svg') }}" alt="Drapeau du S&eacute;n&eacute;gal">
            <div>
              <p class="republic-title">R&Eacute;PUBLIQUE DU S&Eacute;N&Eacute;GAL</p>
              <p class="republic-motto">Un Peuple &ndash; Un But &ndash; Une Foi</p>
            </div>
          </div>
          <p class="ministry">Minist&egrave;re de l&rsquo;Emploi et de la Formation<br>Professionnelle et Technique</p>
        </div>

        <div class="login-brand">
          <span class="brand-emblem">
            <img src="{{ asset('assets/images/image-fcfa.png') }}" alt="Logo SICORE - Syst&egrave;me Int&eacute;gr&eacute; des COrps &Eacute;mergents">
          </span>
          <h1>SICORE</h1>
          <p>Syst&egrave;me Int&eacute;gr&eacute; des COrps &Eacute;mergents</p>
        </div>
      </div>

      <div class="login-right">
        <div class="auth-panel">
          <span class="auth-kicker">Bienvenue sur SICORE</span>
          <p class="auth-subtitle">Connectez-vous avec vos identifiants pour acc&eacute;der &agrave; votre espace s&eacute;curis&eacute;.</p>

          @if (session('status'))<p class="form-status" role="status">{{ session('status') }}</p>@endif
          <form class="form-stack" method="POST" action="{{ route('login.submit') }}" data-login-form>
            @csrf
            <div class="field-group">
              <label for="login">Login</label>
              <div class="input-shell">
                <span class="input-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                <input class="login-input" id="login" type="text" name="login" value="{{ old('login') }}" autocomplete="username" required autofocus aria-describedby="login-errors">
              </div>
              @error('login')<p id="login-errors" class="form-status" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="field-group">
              <label for="password">Mot de passe</label>
              <div class="password-field">
                <span class="input-icon" aria-hidden="true"><i class="fa-solid fa-lock"></i></span>
                <input class="login-input" id="password" type="password" name="password" placeholder="Votre mot de passe" autocomplete="current-password" required>
                <button class="password-toggle" type="button" data-password-toggle="#password" aria-label="Afficher le mot de passe" aria-pressed="false">
                  <i class="fa-solid fa-eye" aria-hidden="true"></i>
                </button>
              </div>
              @error('password')<p class="form-status" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="form-options">
              <label class="check-label">
                <input type="checkbox" name="remember" value="1">
                <span>Se souvenir de moi</span>
              </label>
              <a class="forgot-link" href="#">Mot de passe oubli&eacute; ?</a>
            </div>

            <button class="login-button" type="submit" data-submit-button>
              <i class="fa-solid fa-right-to-bracket" aria-hidden="true"></i>
              <span data-submit-label>Se connecter</span>
            </button>
          </form>

          <p class="auth-footer">Besoin d&rsquo;aide pour acc&eacute;der &agrave; votre compte ? <a href="#">Contactez l&rsquo;administrateur du syst&egrave;me.</a></p>
        </div>
      </div>
    </section>
  </main>
@endsection

@push('scripts')
  <script src="{{ asset('assets/js/app.js') }}" defer></script>
  <script src="{{ asset('assets/js/notifications.js') }}" defer></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      document.querySelector('[data-login-form]')?.addEventListener('submit', function () {
        const button = this.querySelector('[data-submit-button]');
        button.disabled = true; button.setAttribute('aria-busy', 'true');
        this.querySelector('[data-submit-label]').textContent = 'Connexion…';
      });
    });
  </script>
@endpush

