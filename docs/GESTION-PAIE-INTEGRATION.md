# Intégration de la gestion de la paie

Le frontend de la paie dépend de l'API et de MySQL. Le bandeau
`Service SICORE indisponible` signifie que l'appel à l'API a échoué ; il ne
doit pas être remplacé par des données fictives.

## 1. Configuration du backend

Depuis `sicoreBack`, conserver le fichier `.env` existant. S'il n'existe pas,
le créer depuis `.env.example`, puis vérifier au minimum :

```dotenv
APP_URL=http://127.0.0.1:8000
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sicoreproject_db
DB_USERNAME=root
DB_PASSWORD=
FRONTEND_URL=http://127.0.0.1:8001
```

Avec XAMPP, démarrer MySQL, puis exécuter :

```powershell
composer install
php artisan key:generate
php artisan optimize:clear
php artisan migrate:status
php artisan migrate --force
php artisan db:seed --class=GestionPaieSeeder
php artisan serve --host=127.0.0.1 --port=8000
```

Ne jamais utiliser `migrate:fresh` sur une base partagée. Sauvegarder la base
avant d'appliquer des migrations sur un environnement d'intégration.

Les exports Word requièrent l'extension PHP `gd`. Sous XAMPP, vérifier sa
présence avec `php -m | findstr /I gd` avant `composer install`.

## 2. Configuration du frontend

Dans `sicoreFront/.env` :

```dotenv
APP_URL=http://127.0.0.1:8001
API_BASE_URL=http://127.0.0.1:8000/api
```

Puis exécuter :

```powershell
php artisan optimize:clear
php artisan serve --host=127.0.0.1 --port=8001
```

## 3. Contrôle rapide après fusion

```powershell
curl.exe -i http://127.0.0.1:8000/api/health
curl.exe -i -H "Accept: application/json" http://127.0.0.1:8000/api/payroll/pages/paie-etats-presence
```

Le premier appel doit répondre `200`. Le second doit répondre `401` sans
jeton : cette réponse confirme que le backend et la route de paie fonctionnent.
Après connexion depuis le frontend, la page `/paie/etats-presence` doit
répondre `200` et afficher les données issues du backend.

En cas d'erreur `500`, consulter immédiatement la dernière entrée de
`sicoreBack/storage/logs/laravel.log`. Les causes les plus fréquentes sont un
MySQL arrêté, une base absente ou des migrations non appliquées.
