<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LegacyAdminController;
use App\Http\Controllers\LegacyAjaxController;
use App\Http\Controllers\PlanningController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest.legacy')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::get('/logout.php', function (Request $request) {
    $request->session()->invalidate();
    $request->session()->regenerateToken();
    return redirect()->route('login');
})->name('logout.get');

Route::middleware('legacy.auth')->group(function (): void {
    Route::get('/dashboard', [PlanningController::class, 'index'])->name('dashboard');
    Route::get('/change-password', [PlanningController::class, 'changePassword'])->name('password.change');
    Route::post('/change-password', [PlanningController::class, 'updatePassword'])->name('password.change.submit');

    Route::middleware('legacy.admin')->group(function (): void {
        Route::get('/admin/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        Route::post('/admin_create_user.php', [LegacyAdminController::class, 'createUser'])->middleware('feature:manage_users');
        Route::post('/admin_toggle_user.php', [LegacyAdminController::class, 'toggleUser'])->middleware('feature:manage_users');
        Route::post('/admin_delete_user.php', [LegacyAdminController::class, 'deleteUser'])->middleware('feature:manage_users');
        Route::post('/admin_edit_user.php', [LegacyAdminController::class, 'editUser'])->middleware('feature:manage_users');
        Route::get('/admin_users_fragment.php', [LegacyAdminController::class, 'usersFragment'])->middleware('feature:manage_users');

        Route::post('/admin_export.php', [LegacyAdminController::class, 'export'])->middleware('feature:export_data');
        Route::post('/import_dossiers.php', [LegacyAdminController::class, 'importDossiers'])->middleware('feature:manage_import');
        Route::post('/create_missing_users.php', [LegacyAdminController::class, 'createMissingUsers'])->middleware('feature:manage_users');

        Route::post('/admin_schedule_export.php', [LegacyAdminController::class, 'createSchedule'])->middleware('feature:manage_schedules');
        Route::post('/admin_schedule_delete.php', [LegacyAdminController::class, 'deleteSchedule'])->middleware('feature:manage_schedules');
        Route::post('/admin_schedule_run.php', [LegacyAdminController::class, 'runSchedule'])->middleware('feature:manage_schedules');
    });

    Route::get('/get_utilisateurs_actifs.php', [LegacyAdminController::class, 'activeUsers']);

    Route::match(['get', 'post'], '/actions.php', [LegacyAjaxController::class, 'actions']);
    Route::match(['get', 'post'], '/update_statut.php', [LegacyAjaxController::class, 'updateStatut']);
    Route::match(['get', 'post'], '/update_fin_dossier.php', [LegacyAjaxController::class, 'updateFinDossier']);
    Route::match(['get', 'post'], '/ajax_get_temps_dossier.php', [LegacyAjaxController::class, 'getTempsDossier']);
    Route::match(['get', 'post'], '/ajax_get_dossier.php', [LegacyAjaxController::class, 'getDossier']);
    Route::match(['get', 'post'], '/ajax_update_statut.php', [LegacyAjaxController::class, 'updateStatut']);
    Route::match(['get', 'post'], '/ajax_change_collaborateur.php', [LegacyAjaxController::class, 'changeCollaborateur']);
    Route::match(['get', 'post'], '/ajax_update_dates.php', [LegacyAjaxController::class, 'updateDates']);
    Route::match(['get', 'post'], '/ajax_lever_alerte.php', [LegacyAjaxController::class, 'leverAlerte']);
    Route::match(['get', 'post'], '/ajax_save_ventilation.php', [LegacyAjaxController::class, 'saveVentilation']);
    Route::match(['get', 'post'], '/ajax_update_workflow.php', [LegacyAjaxController::class, 'updateWorkflow']);
    Route::match(['get', 'post'], '/ajax_save_checklist.php', [LegacyAjaxController::class, 'saveChecklist']);
    Route::match(['get', 'post'], '/ajax_create_internal_alert.php', [LegacyAjaxController::class, 'createInternalAlert']);
    Route::match(['get', 'post'], '/ajax_generate_sla_alerts.php', [LegacyAjaxController::class, 'generateSlaAlerts']);
    Route::match(['get', 'post'], '/ajax_campaign_projection.php', [LegacyAjaxController::class, 'campaignProjection']);
    Route::match(['get', 'post'], '/calendrier_dossiers_ajax.php', [LegacyAjaxController::class, 'calendrier']);
});


