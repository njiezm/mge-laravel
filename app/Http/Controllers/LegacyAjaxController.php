<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class LegacyAjaxController extends Controller
{
    private array $workflowSteps = ['reception', 'revision', 'validation_associe', 'envoi', 'depot'];

    /**
     * Sends a simple email notification.
     */
    private function sendNotificationMail(?string $email, string $subject, string $body): void
    {
        $email = trim((string) $email);
        if ($email === '') {
            return;
        }

        try {
            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });
        } catch (\Throwable) {
            // best effort only
        }
    }

    /**
     * Notifies all collaborators attached to a dossier.
     */
    private function notifyDossierUsers(int $dossierId, string $subject, string $body): void
    {
        $emails = DB::table('affectation as a')
            ->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')
            ->where('a.dossier_id', $dossierId)
            ->whereNotNull('u.email')
            ->distinct()
            ->pluck('u.email')
            ->all();

        foreach ($emails as $email) {
            $this->sendNotificationMail((string) $email, $subject, $body);
        }
    }

    public function actions(Request $request): JsonResponse
    {
        if (!session()->has('user_id')) {
            return response()->json(['success' => false, 'message' => 'Non authentifie'], 401);
        }

        $idTemps = (int) ($request->input('id_temps') ?? $request->input('id') ?? 0);
        $action = (string) $request->input('action', '');
        $commentaires = (string) ($request->input('commentaires') ?? $request->input('value') ?? '');

        if ($idTemps <= 0) {
            return response()->json(['success' => false, 'message' => 'ID invalide'], 422);
        }

        $dossierId = DB::table('affectation')->where('id', $idTemps)->value('dossier_id');
        if (! $dossierId) {
            return response()->json(['success' => false, 'message' => 'Dossier non trouve'], 404);
        }

        switch ($action) {
            case 'updateComment':
                DB::table('affectation')->where('id', $idTemps)->update(['commentaires' => $commentaires, 'updated_at' => now()]);
                $this->logAudit('commentaire_update', 'affectation', (string) $idTemps, 'Commentaire dossier mis a jour');
                break;

            case 'toggleRetard':
                $statut = DB::table('dossiers')->where('id', $dossierId)->value('statut');
                $newStatut = $statut === 'declarer_en_retard' ? 'en_cours_traitement' : 'declarer_en_retard';
                DB::table('dossiers')->where('id', $dossierId)->update(['statut' => $newStatut, 'updated_at' => now()]);
                $this->logAudit('statut_toggle_retard', 'dossier', (string) $dossierId, 'Bascule retard');
                $this->notifyDossierUsers(
                    $dossierId,
                    'MG Planner - Statut dossier mis a jour',
                    "Le statut du dossier a change.\n\nNouveau statut: {$newStatut}\n"
                );
                break;

            case 'toggleValid':
                $statut = DB::table('dossiers')->where('id', $dossierId)->value('statut');
                $newStatut = $statut === 'liasse_envoyee' ? 'en_cours_traitement' : 'liasse_envoyee';
                DB::table('dossiers')->where('id', $dossierId)->update(['statut' => $newStatut, 'updated_at' => now()]);
                $this->logAudit('statut_toggle_valid', 'dossier', (string) $dossierId, 'Bascule validation');
                $this->notifyDossierUsers(
                    $dossierId,
                    'MG Planner - Statut dossier mis a jour',
                    "Le statut du dossier a change.\n\nNouveau statut: {$newStatut}\n"
                );
                break;

            default:
                return response()->json(['success' => false, 'message' => 'Action inconnue'], 422);
        }

        return response()->json(['success' => true]);
    }

    public function updateStatut(Request $request): JsonResponse
    {
        if (!session()->has('user_id')) {
            return response()->json(['success' => false, 'message' => 'Non authentifie'], 401);
        }

        $dossierId = (int) ($request->input('dossier_id') ?? $request->input('id_dossier') ?? 0);
        $statut = (string) $request->input('statut', 'declarer_en_retard');

        if ($dossierId <= 0) {
            return response()->json(['success' => false, 'message' => 'dossier_id invalide'], 422);
        }

        DB::table('dossiers')->where('id', $dossierId)->update(['statut' => $statut, 'updated_at' => now()]);
        $this->logAudit('statut_update', 'dossier', (string) $dossierId, 'Mise a jour du statut');
        $this->notifyDossierUsers(
            $dossierId,
            'MG Planner - Statut dossier mis a jour',
            "Le statut du dossier a ete mis a jour.\n\nNouveau statut: {$statut}\n"
        );

        return response()->json(['success' => true]);
    }

    public function updateWorkflow(Request $request): JsonResponse
    {
        $dossierId = (int) ($request->input('dossier_id') ?? 0);
        $newStep = (string) ($request->input('workflow_step') ?? '');
        $piecesOk = $request->has('pieces_critiques_ok') ? (bool) $request->boolean('pieces_critiques_ok') : null;

        if ($dossierId <= 0 || ! in_array($newStep, $this->workflowSteps, true)) {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        $dossier = DB::table('dossiers')->where('id', $dossierId)->first(['workflow_step', 'pieces_critiques_ok']);
        if (! $dossier) {
            return response()->json(['success' => false, 'message' => 'Dossier introuvable'], 404);
        }

        $currentIdx = array_search((string) $dossier->workflow_step, $this->workflowSteps, true);
        $targetIdx = array_search($newStep, $this->workflowSteps, true);
        $piecesState = $piecesOk ?? (bool) $dossier->pieces_critiques_ok;

        if ($currentIdx !== false && $targetIdx !== false && $targetIdx > $currentIdx && ! $piecesState) {
            return response()->json([
                'success' => false,
                'message' => 'Pieces critiques manquantes. Impossible de passer a l etape suivante.',
            ], 422);
        }

        $payload = ['workflow_step' => $newStep, 'updated_at' => now()];
        if ($piecesOk !== null) {
            $payload['pieces_critiques_ok'] = $piecesOk;
        }

        DB::table('dossiers')->where('id', $dossierId)->update($payload);
        $this->logAudit('workflow_update', 'dossier', (string) $dossierId, 'Mise a jour workflow fiscal', [
            'from' => (string) $dossier->workflow_step,
            'to' => $newStep,
            'pieces_critiques_ok' => $payload['pieces_critiques_ok'] ?? (bool) $dossier->pieces_critiques_ok,
        ]);

        return response()->json(['success' => true]);
    }

    public function saveChecklist(Request $request): JsonResponse
    {
        $dossierId = (int) ($request->input('dossier_id') ?? 0);
        $items = $request->input('items', []);
        $userId = (int) session('user_id', 0);

        if ($dossierId <= 0 || ! is_array($items) || $items === []) {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                $category = (string) ($item['category'] ?? 'liasse');
                $itemLabel = trim((string) ($item['item_label'] ?? ''));
                $isRequired = (bool) ($item['is_required'] ?? true);
                $isDone = (bool) ($item['is_done'] ?? false);

                if ($itemLabel === '') {
                    continue;
                }

                DB::table('dossier_checklists')->updateOrInsert(
                    [
                        'dossier_id' => $dossierId,
                        'category' => $category,
                        'item_label' => $itemLabel,
                    ],
                    [
                        'is_required' => $isRequired,
                        'is_done' => $isDone,
                        'done_by' => $isDone ? $userId : null,
                        'done_at' => $isDone ? now() : null,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }

            $requiredNotDone = DB::table('dossier_checklists')
                ->where('dossier_id', $dossierId)
                ->where('is_required', true)
                ->where('is_done', false)
                ->count();

            DB::table('dossiers')->where('id', $dossierId)->update([
                'pieces_critiques_ok' => $requiredNotDone === 0,
                'updated_at' => now(),
            ]);

            DB::commit();

            $this->logAudit('checklist_update', 'dossier', (string) $dossierId, 'Checklist fiscale mise a jour');

            return response()->json(['success' => true, 'pieces_critiques_ok' => $requiredNotDone === 0]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Erreur sauvegarde checklist'], 500);
        }
    }

    public function createInternalAlert(Request $request): JsonResponse
    {
        $dossierId = (int) ($request->input('dossier_id') ?? 0);
        $title = trim((string) ($request->input('title') ?? ''));
        $message = trim((string) ($request->input('message') ?? ''));
        $level = (string) ($request->input('level') ?? 'info');
        $targetRole = strtolower((string) ($request->input('target_role') ?? '')); 
        $dueHours = (int) ($request->input('due_hours') ?? 24);

        if ($dossierId <= 0 || $title === '') {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        $query = DB::table('utilisateurs')->where('actif', true);
        if ($targetRole !== '' && $targetRole !== 'all') {
            $query->whereRaw('LOWER(role) like ?', ["%{$targetRole}%"]);
        }

        $users = $query->get(['id']);
        if ($users->isEmpty()) {
            $users = collect([(object) ['id' => (int) session('user_id', 0)]]);
        }

        foreach ($users as $user) {
            DB::table('internal_alerts')->insert([
                'dossier_id' => $dossierId,
                'user_id' => (int) $user->id,
                'level' => $level,
                'title' => $title,
                'message' => $message,
                'status' => 'open',
                'due_at' => now()->addHours(max(1, $dueHours)),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->logAudit('internal_alert_create', 'dossier', (string) $dossierId, 'Alerte interne creee', [
            'title' => $title,
            'target_role' => $targetRole,
            'count' => $users->count(),
        ]);

        return response()->json(['success' => true]);
    }

    public function generateSlaAlerts(): JsonResponse
    {
        $today = now()->toDateString();
        $soon = now()->addDays(2)->toDateString();

        $dossiers = DB::table('dossiers as d')
            ->join('affectation as a', 'a.dossier_id', '=', 'd.id')
            ->whereNotNull('d.deadline')
            ->whereDate('d.deadline', '>=', $today)
            ->whereDate('d.deadline', '<=', $soon)
            ->where('d.statut', '!=', 'liasse_envoyee')
            ->select('d.id', 'd.code_dossier', 'd.deadline', 'a.utilisateur_id')
            ->get();

        $created = 0;

        foreach ($dossiers as $d) {
            $exists = DB::table('internal_alerts')
                ->where('dossier_id', $d->id)
                ->where('status', 'open')
                ->where('title', 'SLA proche echeance')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('internal_alerts')->insert([
                'dossier_id' => $d->id,
                'user_id' => $d->utilisateur_id,
                'level' => 'warning',
                'title' => 'SLA proche echeance',
                'message' => 'Dossier ' . $d->code_dossier . ' proche de la deadline (' . $d->deadline . ')',
                'status' => 'open',
                'due_at' => now()->addHours(6),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->sendNotificationMail(
                (string) DB::table('utilisateurs')->where('id', (int) $d->utilisateur_id)->value('email'),
                'MG Planner - Echeance proche',
                "Un dossier approche de son echeance.\n\n" .
                "Dossier: {$d->code_dossier}\n" .
                "Deadline: {$d->deadline}\n"
            );
            $created++;
        }

        $this->logAudit('sla_alert_generate', 'system', null, 'Generation alertes SLA', ['created' => $created]);

        return response()->json(['success' => true, 'created' => $created]);
    }

    public function campaignProjection(Request $request): JsonResponse
    {
        $extraEtp = max(0, (int) $request->input('extra_etp', 0));
        $delayDays = max(0, (int) $request->input('delay_days', 0));

        $activeUsers = (int) DB::table('utilisateurs')->where('actif', true)->whereRaw("LOWER(role) <> 'admin'")->count();
        $effectiveUsers = max(1, $activeUsers + $extraEtp);
        $capacityPerDay = $effectiveUsers * 7.0;

        $baseQuery = DB::table('dossiers')->where('statut', '!=', 'liasse_envoyee');

        $byPortfolio = (clone $baseQuery)
            ->selectRaw("COALESCE(portefeuille, 'General') as label, COUNT(*) as dossiers, COALESCE(SUM(heure_prevues), COUNT(*)*8) as heures")
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(function ($r) use ($capacityPerDay, $delayDays) {
                $jours = (int) ceil(((float) $r->heures) / max(1.0, $capacityPerDay));
                return [
                    'label' => (string) $r->label,
                    'dossiers' => (int) $r->dossiers,
                    'heures' => (float) $r->heures,
                    'date_estimee' => now()->addDays($jours + $delayDays)->toDateString(),
                ];
            })->values();

        $byTeam = DB::table('dossiers as d')
            ->join('affectation as a', 'a.dossier_id', '=', 'd.id')
            ->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')
            ->where('d.statut', '!=', 'liasse_envoyee')
            ->selectRaw("COALESCE(NULLIF(u.role,''), 'Equipe') as label, COUNT(DISTINCT d.id) as dossiers, COALESCE(SUM(d.heure_prevues), COUNT(DISTINCT d.id)*8) as heures")
            ->groupBy('label')
            ->orderBy('label')
            ->get()
            ->map(function ($r) use ($capacityPerDay, $delayDays) {
                $jours = (int) ceil(((float) $r->heures) / max(1.0, $capacityPerDay));
                return [
                    'label' => (string) $r->label,
                    'dossiers' => (int) $r->dossiers,
                    'heures' => (float) $r->heures,
                    'date_estimee' => now()->addDays($jours + $delayDays)->toDateString(),
                ];
            })->values();

        return response()->json([
            'success' => true,
            'meta' => [
                'active_users' => $activeUsers,
                'extra_etp' => $extraEtp,
                'delay_days' => $delayDays,
                'capacity_per_day' => $capacityPerDay,
            ],
            'by_team' => $byTeam,
            'by_portfolio' => $byPortfolio,
        ]);
    }

    public function updateFinDossier(Request $request): JsonResponse
    {
        $dossierId = (int) $request->input('dossier_id', 0);
        $newDateFin = $request->input('new_date_fin');

        if ($dossierId <= 0 || ! $newDateFin) {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        DB::table('dossiers')->where('id', $dossierId)->update(['date_fin' => $newDateFin, 'updated_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function getTempsDossier(Request $request): JsonResponse
    {
        $codeDossier = (string) $request->query('codeDossier', '');

        $rows = DB::table('dossiers as d')
            ->join('affectation as a', 'a.dossier_id', '=', 'd.id')
            ->where('d.code_dossier', $codeDossier)
            ->select('a.id', DB::raw('0 as temps'))
            ->get();

        return response()->json(['success' => true, 'temps' => $rows]);
    }

    public function getDossier(Request $request): JsonResponse
    {
        $id = (int) $request->query('id', 0);

        $dossier = DB::table('dossiers')->where('id', $id)->first();
        if (! $dossier) {
            return response()->json(['success' => false, 'message' => 'Dossier introuvable'], 404);
        }

        $this->ensureChecklistDefaults($id);

        $collaborateurs = DB::table('affectation as a')
            ->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')
            ->leftJoin('ventilation as v', function ($join) use ($id): void {
                $join->on('v.collaborateur_id', '=', 'u.id')->where('v.dossier_id', '=', $id);
            })
            ->where('a.dossier_id', $id)
            ->select([
                'u.id',
                'u.nom',
                'u.prenom',
                'u.role',
                DB::raw('COALESCE(v.janvier, 0) as janvier'),
                DB::raw('COALESCE(v.fevrier, 0) as fevrier'),
                DB::raw('COALESCE(v.mars, 0) as mars'),
                DB::raw('COALESCE(v.avril, 0) as avril'),
                DB::raw('COALESCE(v.mai, 0) as mai'),
            ])
            ->get();

        $tous = DB::table('utilisateurs')
            ->where('actif', true)
            ->select('id', 'nom', 'prenom', 'role')
            ->orderBy('nom')
            ->get();

        $checklist = DB::table('dossier_checklists')
            ->where('dossier_id', $id)
            ->orderBy('category')
            ->orderBy('item_label')
            ->get(['category', 'item_label', 'is_required', 'is_done']);

        $alerts = DB::table('internal_alerts')
            ->where('dossier_id', $id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'level', 'title', 'message', 'status', 'due_at', 'created_at']);

        $history = DB::table('ventilation_history as h')
            ->leftJoin('utilisateurs as u', 'u.id', '=', 'h.changed_by')
            ->where('h.dossier_id', $id)
            ->orderByDesc('h.created_at')
            ->limit(25)
            ->get([
                'h.mois',
                'h.old_value',
                'h.new_value',
                'h.delta',
                'h.justification',
                'h.created_at',
                DB::raw("COALESCE(u.code_utilisateur, 'system') as changed_by_code"),
            ]);

        return response()->json([
            'success' => true,
            'dossier' => [
                'id' => $dossier->id,
                'code_dossier' => $dossier->code_dossier,
                'nom_client' => $dossier->nom_client,
                'statut' => $dossier->statut,
                'date_debut' => $dossier->date_debut,
                'date_fin' => $dossier->date_fin,
                'workflow_step' => $dossier->workflow_step ?? 'reception',
                'pieces_critiques_ok' => (bool) ($dossier->pieces_critiques_ok ?? false),
                'collaborateurs' => $collaborateurs,
                'tous_collaborateurs' => $tous,
                'checklist' => $checklist,
                'alerts' => $alerts,
                'ventilation_history' => $history,
            ],
        ]);
    }

    public function changeCollaborateur(Request $request): JsonResponse
    {
        $dossierId = (int) $request->input('dossier_id', 0);
        $utilisateurId = (int) $request->input('utilisateur_id', 0);

        if ($dossierId <= 0 || $utilisateurId <= 0) {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        $firstAffectationId = DB::table('affectation')->where('dossier_id', $dossierId)->value('id');
        if (! $firstAffectationId) {
            DB::table('affectation')->insert([
                'dossier_id' => $dossierId,
                'utilisateur_id' => $utilisateurId,
                'commentaires' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            DB::table('affectation')->where('id', $firstAffectationId)->update([
                'utilisateur_id' => $utilisateurId,
                'updated_at' => now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

    public function updateDates(Request $request): JsonResponse
    {
        $dossierId = (int) $request->input('dossier_id', 0);
        $dateDebut = $request->input('date_debut');
        $dateFin = $request->input('date_fin');

        if ($dossierId <= 0 || ! $dateDebut || ! $dateFin) {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        DB::table('dossiers')->where('id', $dossierId)->update([
            'date_debut' => $dateDebut,
            'date_fin' => $dateFin,
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function leverAlerte(Request $request): JsonResponse
    {
        $dossierId = (int) $request->input('dossier_id', 0);
        if ($dossierId <= 0) {
            return response()->json(['success' => false, 'message' => 'dossier_id invalide'], 422);
        }

        DB::table('dossiers')->where('id', $dossierId)->update([
            'statut' => 'en_cours_traitement',
            'updated_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function saveVentilation(Request $request): JsonResponse
    {
        $dossierId = (int) ($request->input('dossier_id') ?? 0);
        $ventilation = $request->input('ventilation', []);
        $justification = trim((string) ($request->input('justification') ?? ''));
        $period = (string) ($request->input('period') ?? now()->format('Y-m'));
        $competence = trim((string) ($request->input('competence') ?? 'general'));
        $changedBy = (int) session('user_id', 0);

        if ($dossierId <= 0 || ! is_array($ventilation) || $ventilation === []) {
            return response()->json(['success' => false, 'message' => 'Parametres invalides'], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($ventilation as $collaborateurId => $mois) {
                $collaborateurId = (int) $collaborateurId;
                if ($collaborateurId <= 0 || ! is_array($mois)) {
                    continue;
                }

                $existing = DB::table('ventilation')
                    ->where('dossier_id', $dossierId)
                    ->where('collaborateur_id', $collaborateurId)
                    ->first();

                $oldValues = [
                    'janvier' => (float) ($existing->janvier ?? 0),
                    'fevrier' => (float) ($existing->fevrier ?? 0),
                    'mars' => (float) ($existing->mars ?? 0),
                    'avril' => (float) ($existing->avril ?? 0),
                    'mai' => (float) ($existing->mai ?? 0),
                ];

                $newValues = [
                    'janvier' => (float) ($mois['janvier'] ?? 0),
                    'fevrier' => (float) ($mois['fevrier'] ?? 0),
                    'mars' => (float) ($mois['mars'] ?? 0),
                    'avril' => (float) ($mois['avril'] ?? 0),
                    'mai' => (float) ($mois['mai'] ?? 0),
                ];

                foreach ($newValues as $month => $newValue) {
                    $oldValue = $oldValues[$month];
                    $delta = round($newValue - $oldValue, 2);
                    if (abs($delta) < 0.01) {
                        continue;
                    }

                    if (abs($delta) >= 20 && $justification === '') {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Justification obligatoire pour un ecart >= 20h',
                        ], 422);
                    }

                    DB::table('ventilation_history')->insert([
                        'dossier_id' => $dossierId,
                        'collaborateur_id' => $collaborateurId,
                        'mois' => $month,
                        'old_value' => $oldValue,
                        'new_value' => $newValue,
                        'delta' => $delta,
                        'justification' => $justification !== '' ? $justification : null,
                        'changed_by' => $changedBy > 0 ? $changedBy : null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('ventilation')->updateOrInsert(
                    [
                        'dossier_id' => $dossierId,
                        'collaborateur_id' => $collaborateurId,
                    ],
                    [
                        'janvier' => $newValues['janvier'],
                        'fevrier' => $newValues['fevrier'],
                        'mars' => $newValues['mars'],
                        'avril' => $newValues['avril'],
                        'mai' => $newValues['mai'],
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );

                DB::table('ventilation_entries')->insert([
                    'dossier_id' => $dossierId,
                    'collaborateur_id' => $collaborateurId,
                    'periode' => $period . '-01',
                    'competence' => $competence !== '' ? $competence : 'general',
                    'heures' => array_sum($newValues),
                    'justification' => $justification !== '' ? $justification : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            $this->logAudit('ventilation_update', 'dossier', (string) $dossierId, 'Ventilation mise a jour');

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Erreur enregistrement ventilation'], 500);
        }
    }

    public function calendrier(Request $request): JsonResponse
    {
        $allowedStatuts = [
            'non_traite',
            'en_cours_traitement',
            'revu_en_cours',
            'revu_associe',
            'liasse_envoyee',
            'declarer_en_retard',
        ];

        $statuses = $request->input('statuses', []);
        if (is_string($statuses)) {
            $statuses = array_filter(array_map('trim', explode(',', $statuses)));
        }
        if (! is_array($statuses)) {
            $statuses = [];
        }
        $statuses = array_values(array_intersect($allowedStatuts, $statuses));

        $userFilter = (string) $request->input('user', 'moi');
        $sessionUserId = (int) session('user_id', 0);

        $query = DB::table('dossiers as d')
            ->leftJoin('affectation as a', 'a.dossier_id', '=', 'd.id')
            ->whereNotNull('d.date_debut')
            ->whereNotNull('d.date_fin')
            ->select([
                DB::raw('d.id::text as id'),
                DB::raw('d.code_dossier as title'),
                DB::raw('d.date_debut as start'),
                DB::raw('d.date_fin as end'),
                'd.statut',
            ]);

        if ($statuses !== []) {
            $query->whereIn('d.statut', $statuses);
        }

        if ($userFilter === 'moi' && $sessionUserId > 0) {
            $query->where('a.utilisateur_id', $sessionUserId);
        } elseif ($userFilter !== 'all') {
            $userFilterId = (int) $userFilter;
            if ($userFilterId > 0) {
                $query->where('a.utilisateur_id', $userFilterId);
            }
        }

        $events = $query
            ->groupBy('d.id', 'd.code_dossier', 'd.date_debut', 'd.date_fin', 'd.statut')
            ->orderBy('d.date_debut')
            ->limit(500)
            ->get();

        return response()->json(['success' => true, 'events' => $events]);
    }

    private function ensureChecklistDefaults(int $dossierId): void
    {
        $count = DB::table('dossier_checklists')->where('dossier_id', $dossierId)->count();
        if ($count > 0) {
            return;
        }

        $defaults = [
            ['category' => 'TVA', 'item_label' => 'Pieces TVA recues', 'is_required' => true],
            ['category' => 'IS', 'item_label' => 'Balance IS verifiee', 'is_required' => true],
            ['category' => 'liasse', 'item_label' => 'Liasse prete a revision', 'is_required' => true],
            ['category' => 'annexes', 'item_label' => 'Annexes legales completees', 'is_required' => true],
        ];

        foreach ($defaults as $item) {
            DB::table('dossier_checklists')->insert([
                'dossier_id' => $dossierId,
                'category' => $item['category'],
                'item_label' => $item['item_label'],
                'is_required' => $item['is_required'],
                'is_done' => false,
                'done_by' => null,
                'done_at' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function logAudit(string $action, ?string $targetType, ?string $targetId, ?string $message = null, array $meta = []): void
    {
        try {
            DB::table('audit_logs')->insert([
                'user_id' => session('user_id'),
                'user_code' => session('user_nom'),
                'role' => session('user_role'),
                'action' => $action,
                'target_type' => $targetType,
                'target_id' => $targetId,
                'message' => $message,
                'meta' => $meta !== [] ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
                'ip' => request()->ip(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            // silent
        }
    }
}

