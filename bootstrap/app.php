<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'legacy.auth' => \App\Http\Middleware\EnsureLegacyAuthenticated::class,
            'legacy.admin' => \App\Http\Middleware\EnsureAdminRole::class,
            'guest.legacy' => \App\Http\Middleware\RedirectIfLegacyAuthenticated::class,
            'feature' => \App\Http\Middleware\EnsureFeaturePermission::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            'actions.php',
            'update_statut.php',
            'update_fin_dossier.php',
            'ajax_get_temps_dossier.php',
            'ajax_get_dossier.php',
            'ajax_update_statut.php',
            'ajax_change_collaborateur.php',
            'ajax_update_dates.php',
            'ajax_lever_alerte.php',
            'ajax_save_ventilation.php',
            'calendrier_dossiers_ajax.php',
            'admin_create_user.php',
            'admin_toggle_user.php',
            'admin_delete_user.php',
            'admin_edit_user.php',
            'admin_export.php',
            'import_dossiers.php',
            'create_missing_users.php',
            'admin_schedule_export.php',
            'admin_schedule_delete.php',
            'admin_schedule_run.php',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
