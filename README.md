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
