<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $totalUsers = DB::table('utilisateurs')->count();
        $dossiersAnnee = DB::table('dossiers')->count();

        $roles = DB::table('utilisateurs')->select('role', DB::raw('COUNT(*) as total'))->groupBy('role')->pluck('total', 'role')->toArray();
        $actifs = DB::table('utilisateurs')->select('actif', DB::raw('COUNT(*) as total'))->groupBy('actif')->pluck('total', 'actif')->toArray();

        $users = DB::table('utilisateurs')->select('id', 'code_utilisateur', 'nom', 'prenom', 'email', 'role', 'actif')->orderBy('nom')->get()->map(fn($r) => (array) $r)->all();

        $kpiRetards = (int) DB::table('dossiers')->where('statut', 'declarer_en_retard')->count();
        $kpiBloques = (int) DB::table('dossiers')->whereNotNull('deadline')->whereDate('deadline', '<', now()->toDateString())->where('statut', '!=', 'liasse_envoyee')->count();
        $kpiCharge = DB::table('affectation as a')
            ->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')
            ->join('dossiers as d', 'd.id', '=', 'a.dossier_id')
            ->select(DB::raw("COALESCE(u.prenom,'') || ' ' || u.nom as collaborateur"), DB::raw('COUNT(a.id) as nb_dossiers'), DB::raw('COALESCE(SUM(d.heure_prevues),0) as heures_prevues'))
            ->groupBy('u.id', 'u.nom', 'u.prenom')
            ->orderByDesc('nb_dossiers')
            ->limit(10)
            ->get()
            ->map(fn($r) => (array) $r)
            ->all();

        $q = trim((string) $request->query('q', ''));
        $search = ['users' => [], 'dossiers' => [], 'logs' => []];
        if ($q !== '') {
            $search['users'] = DB::table('utilisateurs')
                ->where('code_utilisateur', 'like', "%{$q}%")
                ->orWhere('nom', 'like', "%{$q}%")
                ->orWhere('prenom', 'like', "%{$q}%")
                ->orWhere('email', 'like', "%{$q}%")
                ->limit(20)
                ->get(['id', 'code_utilisateur', 'nom', 'prenom', 'email', 'role', 'actif'])
                ->map(fn($r) => (array) $r)->all();

            $search['dossiers'] = DB::table('dossiers')
                ->where('code_dossier', 'like', "%{$q}%")
                ->orWhere('nom_client', 'like', "%{$q}%")
                ->orWhere('statut', 'like', "%{$q}%")
                ->limit(20)
                ->get(['id', 'code_dossier', 'nom_client', 'date_debut', 'date_fin', 'deadline', 'statut'])
                ->map(fn($r) => (array) $r)->all();

            if (DB::getSchemaBuilder()->hasTable('audit_logs')) {
                $search['logs'] = DB::table('audit_logs')
                    ->where('action', 'like', "%{$q}%")
                    ->orWhere('user_code', 'like', "%{$q}%")
                    ->orWhere('message', 'like', "%{$q}%")
                    ->orderByDesc('id')
                    ->limit(30)
                    ->get(['created_at', 'user_code', 'role', 'action', 'message'])
                    ->map(fn($r) => (array) $r)->all();
            }
        }

        $auditLogs = DB::getSchemaBuilder()->hasTable('audit_logs')
            ? DB::table('audit_logs')->orderByDesc('id')->limit(30)->get(['created_at', 'user_code', 'role', 'action', 'message'])->map(fn($r) => (array) $r)->all()
            : [];

        $schedules = DB::getSchemaBuilder()->hasTable('scheduled_exports')
            ? DB::table('scheduled_exports')->orderByDesc('id')->get()->map(fn($r) => (array) $r)->all()
            : [];

        return view('admin.dashboard', [
            'user_nom' => session('user_nom'),
            'total_users' => $totalUsers,
            'dossiers_annee' => $dossiersAnnee,
            'roles_count' => $roles,
            'actif_count' => $actifs,
            'users' => $users,
            'kpi_retards' => $kpiRetards,
            'kpi_bloques' => $kpiBloques,
            'kpi_charge' => $kpiCharge,
            'search_q' => $q,
            'search' => $search,
            'audit_logs' => $auditLogs,
            'schedules' => $schedules,
        ]);
    }

}
