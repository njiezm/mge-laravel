<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PlanningController extends Controller
{
    public function index(Request $request)
    {
        $userId = (int) session('user_id');
        $userNom = (string) session('user_nom');
        $userRole = (string) session('user_role', 'Collaborateur');
        $today = now()->toDateString();

        $statutsOptions = [
            'non_traite' => 'Recu Non Traite',
            'en_cours_traitement' => 'Recu En Cours De Traitement',
            'revu_en_cours' => 'Revu En Cours',
            'revu_associe' => 'Revu Associe',
            'liasse_envoyee' => 'Liasse Envoyee',
            'declarer_en_retard' => 'Declarer En Retard',
        ];

        $recordsPerPageGlobal = (int) $request->query('rows_global', 10);
        $currentPageGlobal = max(1, (int) $request->query('page_global', 1));
        $recordsPerPagePerso = (int) $request->query('rows_perso', 10);
        $currentPagePerso = max(1, (int) $request->query('page_perso', 1));
        $recordsPerPageEcheances = (int) $request->query('rows_echeances', 10);
        $currentPageEcheances = max(1, (int) $request->query('page_echeances', 1));

        $totalGlobal = (int) DB::table('dossiers as d')
            ->leftJoin('affectation as a', 'a.dossier_id', '=', 'd.id')
            ->count();
        $totalPagesGlobal = (int) max(1, ceil($totalGlobal / max(1, $recordsPerPageGlobal)));

        $planningAll = DB::table('dossiers as d')
            ->leftJoin('affectation as a', 'a.dossier_id', '=', 'd.id')
            ->leftJoin('utilisateurs as u', 'a.utilisateur_id', '=', 'u.id')
            ->select([
                'a.id as temps_id', 'd.id as dossier_id', 'd.code_dossier as code', 'd.nom_client as dossier_nom',
                'd.deadline', 'd.date_debut', 'd.date_fin', 'a.commentaires', 'u.nom as collaborateur', 'u.role',
            ])
            ->orderBy('d.deadline')->orderBy('d.code_dossier')->orderBy('u.nom')
            ->offset(($currentPageGlobal - 1) * $recordsPerPageGlobal)->limit($recordsPerPageGlobal)
            ->get()
            ->map(function ($r): array {
                $row = (array) $r;
                $row['collaborateur'] = $row['collaborateur'] ?? 'Non affecté';
                $row['role'] = $row['role'] ?? '-';
                return $row;
            })
            ->all();

        $totalPerso = (int) DB::table('affectation')->where('utilisateur_id', $userId)->count();
        $totalPagesPerso = (int) max(1, ceil($totalPerso / max(1, $recordsPerPagePerso)));

        $planningUser = DB::table('affectation as a')
            ->join('dossiers as d', 'a.dossier_id', '=', 'd.id')
            ->where('a.utilisateur_id', $userId)
            ->select(['a.id as temps_id', 'd.id as dossier_id', 'd.code_dossier as code', 'd.nom_client as dossier_nom', 'd.deadline', 'd.date_debut', 'd.date_fin', 'a.commentaires', 'd.statut'])
            ->orderBy('d.date_debut')->orderBy('d.deadline')
            ->offset(($currentPagePerso - 1) * $recordsPerPagePerso)->limit($recordsPerPagePerso)
            ->get()->map(fn($r) => (array) $r)->all();

        $currentDossier = DB::table('affectation as a')->join('dossiers as d', 'a.dossier_id', '=', 'd.id')
            ->where('a.utilisateur_id', $userId)
            ->whereDate('d.date_debut', '<=', $today)->whereDate('d.date_fin', '>=', $today)
            ->select('d.code_dossier as code', 'd.nom_client as dossier_nom', 'd.date_debut', 'd.date_fin')->orderBy('d.date_debut')->first();

        $nextDossier = DB::table('affectation as a')->join('dossiers as d', 'a.dossier_id', '=', 'd.id')
            ->where('a.utilisateur_id', $userId)
            ->whereDate('d.date_debut', '>', $today)
            ->select('d.code_dossier as code', 'd.nom_client as dossier_nom', 'd.date_debut', 'd.date_fin')->orderBy('d.date_debut')->first();

        $retardCount = (int) DB::table('dossiers')->where('statut', 'declarer_en_retard')->count();

        $mesDossiers = DB::table('affectation as a')->join('dossiers as d', 'a.dossier_id', '=', 'd.id')
            ->where('a.utilisateur_id', $userId)
            ->select('d.id', 'd.code_dossier as code', 'd.nom_client as nom', 'd.date_debut', 'd.date_fin', 'd.statut')
            ->orderBy('d.date_debut')->get()->map(fn($r) => (array) $r)->all();

        $filterStatut = (string) $request->query('filterStatut', '');
        $filterUserEcheancesCheckbox = $request->boolean('filterUserEcheancesCheckbox');

        $echeancesQuery = DB::table('dossiers as d');
        if ($filterUserEcheancesCheckbox) {
            $echeancesQuery->whereIn('d.id', fn($q) => $q->select('dossier_id')->from('affectation')->where('utilisateur_id', $userId));
        }
        if ($filterStatut !== '' && array_key_exists($filterStatut, $statutsOptions)) {
            $echeancesQuery->where('d.statut', $filterStatut);
        }

        $totalEcheances = (clone $echeancesQuery)->count();
        $totalPagesEcheances = (int) max(1, ceil($totalEcheances / max(1, $recordsPerPageEcheances)));

        $dossiersEcheances = $echeancesQuery->select('d.*', DB::raw('COALESCE(d.heure_prevues,0) as heure_prevues'))
            ->orderBy('d.deadline')->offset(($currentPageEcheances - 1) * $recordsPerPageEcheances)->limit($recordsPerPageEcheances)
            ->get()->map(fn($r) => (array) $r)->all();

        $usersList = DB::table('utilisateurs')->where('actif', true)->orderBy('nom')->get(['id', 'nom', 'prenom', 'role'])->map(fn($r) => (array) $r)->all();

        $eventsJson = DB::table('dossiers')->whereNotNull('date_debut')->whereNotNull('date_fin')
            ->select([DB::raw('id::text as id'), DB::raw('code_dossier as title'), DB::raw('date_debut as start'), DB::raw('date_fin as end'), 'statut'])
            ->orderBy('date_debut')->limit(500)->get()->map(fn($r) => (array) $r)->all();

        if ($request->query('ajax') === 'true') {
            $tab = (string) $request->query('tab', '');
            if ($tab === 'global') return response()->view('planning.partials.global', compact('planningAll', 'currentPageGlobal', 'recordsPerPageGlobal', 'totalPagesGlobal'));
            if ($tab === 'perso') return response()->view('planning.partials.perso', compact('planningUser', 'statutsOptions', 'currentPagePerso', 'recordsPerPagePerso', 'totalPagesPerso'));
            if ($tab === 'echeances') return response()->view('planning.partials.echeances', compact('dossiersEcheances', 'statutsOptions', 'filterStatut', 'filterUserEcheancesCheckbox', 'currentPageEcheances', 'recordsPerPageEcheances', 'totalPagesEcheances'));
        }

        return view('planning.index', [
            'user_nom' => $userNom, 'user_role' => $userRole, 'today' => $today,
            'planning_all' => $planningAll, 'planning_user' => $planningUser,
            'current_dossier' => $currentDossier ? (array) $currentDossier : null,
            'next_dossier' => $nextDossier ? (array) $nextDossier : null,
            'retard_count' => $retardCount, 'mes_dossiers' => $mesDossiers,
            'statuts_options' => $statutsOptions, 'dossiers_echeances' => $dossiersEcheances,
            'filterStatut' => $filterStatut, 'filterUserEcheancesCheckbox' => $filterUserEcheancesCheckbox,
            'records_per_page_global' => $recordsPerPageGlobal, 'current_page_global' => $currentPageGlobal, 'total_pages_global' => $totalPagesGlobal,
            'records_per_page_perso' => $recordsPerPagePerso, 'current_page_perso' => $currentPagePerso, 'total_pages_perso' => $totalPagesPerso,
            'records_per_page_echeances' => $recordsPerPageEcheances, 'current_page_echeances' => $currentPageEcheances, 'total_pages_echeances' => $totalPagesEcheances,
            'users_list' => $usersList, 'events_json' => $eventsJson, 'pdo' => DB::connection()->getPdo(),
        ]);
    }

    public function changePassword(): View
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $userId = (int) session('user_id');
        DB::table('utilisateurs')->where('id', $userId)->update([
            'mot_de_passe' => Hash::make($data['password']),
            'premiere_connexion' => false,
            'updated_at' => now(),
        ]);

        return strtolower((string) session('user_role')) === 'admin'
            ? redirect()->route('admin.dashboard')->with('status', 'Mot de passe mis a jour.')
            : redirect()->route('dashboard')->with('status', 'Mot de passe mis a jour.');
    }
}
