# Migration MGEXP vers Laravel + Blade + PostgreSQL

## Ce qui est deja migre
- Base Laravel 12 dans `laravel/`
- Authentification session (table `utilisateurs`) via:
  - `GET/POST /login`
  - `POST /logout`
- Dashboard collaborateur: `GET /dashboard`
- Dashboard admin: `GET /admin/dashboard`
- Middleware metier:
  - `legacy.auth`
  - `legacy.admin`
- Migration PostgreSQL pour:
  - `utilisateurs`
  - `dossiers`
  - `affectation`
- Assets visuels repris a l'identique:
  - `public/assets/styles.css`
  - `public/assets/images/logo-MG.png`
  - `public/assets/images/logo-MGB.png`
- Dependances metier ajoutees:
  - `barryvdh/laravel-dompdf`
  - `phpoffice/phpspreadsheet`
  - `phpmailer/phpmailer`

## Configuration PostgreSQL
1. Modifier `laravel/.env`:
   - `DB_HOST`
   - `DB_PORT`
   - `DB_DATABASE`
   - `DB_USERNAME`
   - `DB_PASSWORD`
2. Executer:
   - `php artisan migrate`

## Lancement
- `php artisan serve`

## Ameliorations recommandees (prochaine iteration)
1. Refaire tous les endpoints AJAX historiques en API Laravel (`/api/...`) + Validation FormRequest.
2. Implementer le vrai flux `premiere_connexion` (formulaire changement mot de passe + politique de complexite).
3. Remplacer les requetes SQL inline par Services + Repositories pour la logique planning/import.
4. Ajouter import batch queue (Excel/CSV) avec jobs Laravel et suivi d'execution.
5. Ajouter tests feature (auth, role admin, dashboard) + tests unitaires sur regles de statut/retard.
6. Activer observabilite: logs structures, alerting, trace des operations admin.
7. Renforcer securite: rate limiting login, CSRF partout, audit trail des actions critiques.
8. Ajouter seeders/fixtures pour recette et environnement de preprod.
