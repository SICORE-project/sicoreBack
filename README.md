<HEAD
<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

# The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

# sicoreBack

le REPOSITORY pour la partie backend
# SICORE API

L'authentification API utilise exclusivement `utilisateurs.login` et les rôles métier. La table et le modèle Laravel historiques `users` sont conservés temporairement pour compatibilité de migration, mais ne sont reliés à aucun guard et ne doivent recevoir aucun compte métier. Leur suppression fera l'objet d'une migration dédiée après validation de l'équipe.

En production, `APP_ENV=production` et `APP_DEBUG=false` sont obligatoires. Définir aussi `FRONTEND_URL` avec l'origine exacte du frontend, puis exécuter `php artisan migrate --force`.

Les tokens Sanctum du BFF expirent selon `SANCTUM_TOKEN_EXPIRATION` et sont révoqués au logout.
# SICORE Backend API

API Laravel 12 de SICORE, sécurisée par Laravel Sanctum.

## Installation locale

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve --host=127.0.0.1 --port=8000
```

Le `.env` local fourni utilise SQLite. Le compte initial est :

```text
admin@sicore.sn / Sicore@2026
```

## Gestion de la paie

Le backend couvre les périodes, présences, éléments variables, calculs,
bulletins, paiements, restitutions, exports et audits. Les mutations utilisent
des transactions, des contrôles de version et une clé d’idempotence.

La documentation complète se trouve dans
[`../docs/GESTION-PAIE.md`](../docs/GESTION-PAIE.md).

### Import d’une liste de PC actifs

```powershell
php artisan sicore:import-pc "C:\chemin\LISTE PC ACTIFS JANVIER 2026.xls" --period=2026-01 --dry-run
php artisan sicore:import-pc "C:\chemin\LISTE PC ACTIFS JANVIER 2026.xls" --period=2026-01
```

L’import est idempotent et journalisé par empreinte SHA-256. Les comptes
techniques importés ne peuvent pas se connecter et les salaires restent à zéro
jusqu’à leur validation.

Les profils se complètent ensuite dans **Paie non générée**. La grille
contractuelle, l’IRD et les paramètres IPRES sont datés en base ; le vacataire
reçoit automatiquement un salaire de base de 150 000 FCFA. Le calcul global est
bloqué tant qu’un profil contractuel ou vacataire actif reste incomplet.

## Tests

```powershell
php artisan test
```

En production, définir `APP_ENV=production`, `APP_DEBUG=false`, une base
managée et sauvegardée, HTTPS, ainsi que les taux de paie validés.
