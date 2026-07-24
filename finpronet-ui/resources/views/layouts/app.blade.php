<!doctype html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'SICORE')</title>
  @stack('styles')
</head>
<body @yield('body_attributes') data-sicore-role="{{ auth()->check() ? data_get(auth()->user()->data, 'role.libelle') : '' }}">
  @yield('content')

  @stack('scripts')
  @auth
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const role = document.body.dataset.sicoreRole.toLocaleLowerCase('fr');
      if (!['administrateur', 'super administrateur'].includes(role)) {
        document.querySelectorAll('a[href*="/utilisateurs"]').forEach(link => link.closest('li')?.remove());
      }
    });
  </script>
  @endauth
</body>
</html>
