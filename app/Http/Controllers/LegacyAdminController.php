<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LegacyAdminController extends Controller
{
    public function createUser(Request $request): RedirectResponse|JsonResponse
    {
        $plainPassword = (string) $request->input('mot_de_passe', 'Temp@12345');

        DB::table('utilisateurs')->insert([
            'code_utilisateur' => (string) $request->input('code_utilisateur'),
            'nom' => (string) $request->input('nom'),
            'prenom' => (string) $request->input('prenom'),
            'email' => (string) $request->input('email'),
            'mot_de_passe' => Hash::make($plainPassword),
            'role' => (string) $request->input('role', 'collaborateur'),
            'actif' => true,
            'premiere_connexion' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $email = (string) $request->input('email');
        $mailStatus = $this->sendNotificationMail(
            $email,
            'MG Planner - Acces au compte',
            "Votre compte MG Planner a ete cree.\n\n" .
            "Identifiant: " . (string) $request->input('code_utilisateur') . "\n" .
            "Mot de passe temporaire: {$plainPassword}\n\n" .
            "A la premiere connexion, vous devrez changer ce mot de passe."
        );

        $this->audit($request, 'create_user', 'utilisateur', (string) $request->input('code_utilisateur'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur cree avec succes.',
                'notice' => "Mot de passe temporaire configure. L'utilisateur recevra un email de connexion et devra changer son mot de passe a la premiere connexion.",
                'mail_status' => $mailStatus,
                ...$this->usersFragments(),
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    public function toggleUser(Request $request): RedirectResponse|JsonResponse
    {
        $id = (int) $request->input('id');
        $actif = (bool) DB::table('utilisateurs')->where('id', $id)->value('actif');
        DB::table('utilisateurs')->where('id', $id)->update(['actif' => ! $actif, 'updated_at' => now()]);
        $this->audit($request, 'toggle_user', 'utilisateur', (string) $id, 'actif=' . (! $actif ? '1' : '0'));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => ! $actif ? 'Utilisateur activé.' : 'Utilisateur désactivé.',
                ...$this->usersFragments(),
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    public function deleteUser(Request $request): RedirectResponse|JsonResponse
    {
        $id = (int) $request->input('id');
        DB::table('utilisateurs')->where('id', $id)->delete();
        $this->audit($request, 'delete_user', 'utilisateur', (string) $id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur supprimé.',
                ...$this->usersFragments(),
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    public function editUser(Request $request): RedirectResponse|JsonResponse
    {
        $id = (int) $request->input('id');
        $updateData = [
            'code_utilisateur' => (string) $request->input('code_utilisateur'),
            'nom' => (string) $request->input('nom'),
            'prenom' => (string) $request->input('prenom'),
            'email' => (string) $request->input('email'),
            'role' => (string) $request->input('role', 'collaborateur'),
            'updated_at' => now(),
        ];

        $plainPassword = trim((string) $request->input('mot_de_passe', ''));
        if ($plainPassword !== '') {
            $updateData['mot_de_passe'] = Hash::make($plainPassword);
            $updateData['premiere_connexion'] = true;
        }

        DB::table('utilisateurs')->where('id', $id)->update($updateData);

        if ($plainPassword !== '') {
            $this->sendNotificationMail(
                (string) $updateData['email'],
                'MG Planner - Mot de passe mis a jour',
                "Votre mot de passe MG Planner a ete mis a jour par l'administration.\n\n" .
                "Identifiant: " . (string) $updateData['code_utilisateur'] . "\n" .
                "Nouveau mot de passe temporaire: {$plainPassword}\n\n" .
                "Vous devrez le changer lors de votre prochaine connexion."
            );
        }

        $this->audit($request, 'edit_user', 'utilisateur', (string) $id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Utilisateur modifie.',
                'notice' => $plainPassword !== '' ? "Mot de passe mis a jour. L'utilisateur devra le changer a sa prochaine connexion." : null,
                ...$this->usersFragments(),
            ]);
        }

        return redirect()->route('admin.dashboard');
    }

    public function activeUsers(): JsonResponse
    {
        $users = DB::table('utilisateurs')->where('actif', true)->pluck('code_utilisateur')->toArray();
        return response()->json(['users' => $users]);
    }

    public function createMissingUsers(Request $request): JsonResponse
    {
        $users = $request->input('users', []);
        $rolesByUser = (array) $request->input('roles_by_user', []);
        foreach ($users as $code) {
            $role = $this->normalizeRole((string) ($rolesByUser[$code] ?? 'collaborateur'));
            $email = strtolower((string) $code) . '@mgexp.local';
            DB::table('utilisateurs')->updateOrInsert(
                ['code_utilisateur' => (string) $code],
                [
                    'nom' => (string) $code,
                    'prenom' => 'Auto',
                    'email' => $email,
                    'mot_de_passe' => Hash::make('Temp@12345'),
                    'role' => $role,
                    'actif' => true,
                    'premiere_connexion' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $this->sendNotificationMail(
                $email,
                'MG Planner - Compte cree automatiquement',
                "Votre compte MG Planner a ete cree automatiquement.\n\n" .
                "Identifiant: {$code}\n" .
                "Mot de passe temporaire: Temp@12345\n\n" .
                "Vous devrez le modifier a la premiere connexion."
            );
        }

        $this->audit($request, 'create_missing_users', 'utilisateur', null, 'count=' . count($users));
        return response()->json(['success' => true, 'message' => 'Utilisateurs crees']);
    }

    public function importDossiers(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $dossiers = $payload['dossiers'] ?? [];
        $action = $payload['action'] ?? 'preview';
        $overwrite = (bool) ($payload['overwrite'] ?? false);
        $targetYear = (int) ($payload['target_year'] ?? now()->year);
        if ($targetYear < 2000 || $targetYear > 2100) {
            $targetYear = (int) now()->year;
        }

        $mapped = [];
        foreach ($dossiers as $d) {
            $mapped[] = [
                'code_dossier' => trim((string) ($d['code_dossier'] ?? '')),
                'societe' => (string) ($d['societe'] ?? ''),
                'groupe' => (string) ($d['groupe'] ?? ''),
                'collab' => $d['collab'] ?? null,
                'cdm' => $d['cdm'] ?? null,
                'associe' => $d['associe'] ?? null,
                'date_reception' => $d['date_reception'] ?? null,
                'heure_prevues' => (float) ($d['heure_prevues'] ?? 0),
                'date_debut' => $d['date_debut'] ?? now()->toDateString(),
                'date_fin' => $d['date_fin'] ?? now()->addDays(5)->toDateString(),
                'critere' => (int) ($d['critere'] ?? 0),
            ];
        }

        if ($action === 'import') {
            $existingCount = (int) DB::table('dossiers')->count();
            $overwriteMailStatus = 'not_sent';
            if ($existingCount > 0 && ! $overwrite) {
                return response()->json([
                    'success' => false,
                    'requires_overwrite_confirmation' => true,
                    'message' => "La base contient deja {$existingCount} dossiers. Exportez d'abord l'ancien dataset puis confirmez l'ecrasement.",
                ], Response::HTTP_CONFLICT);
            }

            $count = 0;
            $importNotifications = [];
            DB::transaction(function () use ($mapped, $targetYear, &$count, &$importNotifications): void {
                $roleByCode = [];
                foreach ($mapped as $d) {
                    $this->registerRoleCandidate($roleByCode, (string) ($d['collab'] ?? ''), 'collaborateur');
                    $this->registerRoleCandidate($roleByCode, (string) ($d['cdm'] ?? ''), 'chef');
                    $this->registerRoleCandidate($roleByCode, (string) ($d['associe'] ?? ''), 'associe');
                }

                foreach ($roleByCode as $code => $targetRole) {
                    $existing = DB::table('utilisateurs')->where('code_utilisateur', $code)->first();
                    if (! $existing) {
                        DB::table('utilisateurs')->insert([
                            'code_utilisateur' => $code,
                            'nom' => $code,
                            'prenom' => 'Auto',
                            'email' => strtolower($code) . '@mgexp.local',
                            'mot_de_passe' => Hash::make('Temp@12345'),
                            'role' => $targetRole,
                            'actif' => true,
                            'premiere_connexion' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        continue;
                    }

                    $currentRole = $this->normalizeRole((string) ($existing->role ?? 'collaborateur'));
                    if ($this->rolePriority($targetRole) > $this->rolePriority($currentRole)) {
                        DB::table('utilisateurs')
                            ->where('id', (int) $existing->id)
                            ->update(['role' => $targetRole, 'updated_at' => now()]);
                    }
                }

                $userIdsByCode = DB::table('utilisateurs')
                    ->whereIn('code_utilisateur', array_keys($roleByCode))
                    ->pluck('id', 'code_utilisateur')
                    ->toArray();
                $userEmailsById = [];
                if ($userIdsByCode !== []) {
                    $userEmailsById = DB::table('utilisateurs')
                        ->whereIn('id', array_values($userIdsByCode))
                        ->pluck('email', 'id')
                        ->toArray();
                }

                DB::table('affectation')->delete();
                if (DB::getSchemaBuilder()->hasTable('ventilation')) {
                    DB::table('ventilation')->delete();
                }
                if (DB::getSchemaBuilder()->hasTable('ventilation_entries')) {
                    DB::table('ventilation_entries')->delete();
                }
                DB::table('dossiers')->delete();

                $planningState = ['availability' => [], 'user_loads' => [], 'slot_index' => 0];

                foreach ($mapped as $d) {
                    if ($d['code_dossier'] === '') {
                        continue;
                    }

                    $participants = [
                        'collaborateur' => [
                            'code' => trim((string) ($d['collab'] ?? '')),
                            'label' => 'collaborateur',
                            'ratio' => 0.70,
                        ],
                        'chef' => [
                            'code' => trim((string) ($d['cdm'] ?? '')),
                            'label' => 'chef_de_mission',
                            'ratio' => 0.20,
                        ],
                        'associe' => [
                            'code' => trim((string) ($d['associe'] ?? '')),
                            'label' => 'associe',
                            'ratio' => 0.10,
                        ],
                    ];
                    foreach ($participants as $key => $participant) {
                        $code = $participant['code'];
                        $participants[$key]['user_id'] = isset($userIdsByCode[$code]) ? (int) $userIdsByCode[$code] : 0;
                    }

                    [$plannedStart, $plannedEnd, $sharesByRole] = $this->computeImportPlanning($d, $participants, $planningState, $targetYear, count($mapped));

                    $dossierId = DB::table('dossiers')->insertGetId([
                        'code_dossier' => $d['code_dossier'],
                        'nom_client' => trim($d['code_dossier'] . ' - ' . ($d['societe'] ?: 'Client')),
                        'heure_prevues' => $d['heure_prevues'],
                        'critere' => $d['critere'],
                        'date_debut' => $plannedStart,
                        'date_fin' => $plannedEnd,
                        'deadline' => $plannedEnd,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]);

                    $alreadyAssigned = [];
                    foreach ($participants as $roleKey => $participant) {
                        $code = $participant['code'];
                        if ($code === '' || isset($alreadyAssigned[$code])) {
                            continue;
                        }

                        $userId = (int) ($participant['user_id'] ?? 0);
                        if ($userId <= 0) {
                            continue;
                        }

                        $hours = (float) ($sharesByRole[$roleKey] ?? 0.0);
                        $hoursFormatted = number_format($hours, 2, '.', '');

                        DB::table('affectation')->insert([
                            'utilisateur_id' => $userId,
                            'dossier_id' => $dossierId,
                            'commentaires' => 'Import annuel - role: ' . $participant['label'] . ' - heures: ' . $hoursFormatted,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $this->storeImportVentilation($dossierId, $userId, $hours, $plannedStart);
                        $recipientEmail = (string) ($userEmailsById[$userId] ?? '');
                        if ($recipientEmail !== '') {
                            $importNotifications[] = [
                                'email' => $recipientEmail,
                                'subject' => 'MG Planner - Dossier importe',
                                'body' => "Un dossier vous a ete attribue apres import annuel.\n\n" .
                                    "Dossier: {$d['code_dossier']}\n" .
                                    "Role: {$participant['label']}\n" .
                                    "Debut simule: {$plannedStart}\n" .
                                    "Fin simulee: {$plannedEnd}\n" .
                                    "Heures attribuees: {$hoursFormatted}\n",
                            ];
                        }
                        $alreadyAssigned[$code] = true;
                    }

                    $count++;
                }
            });

            foreach ($importNotifications as $notification) {
                $this->sendNotificationMail(
                    (string) $notification['email'],
                    (string) $notification['subject'],
                    (string) $notification['body']
                );
            }

            if ($overwrite && $existingCount > 0) {
                $adminEmail = (string) DB::table('utilisateurs')->where('id', (int) session('user_id', 0))->value('email');
                $overwriteMailStatus = $this->sendNotificationMail(
                    $adminEmail,
                    'MG Planner - Dataset ecrase',
                    "L'import annuel a ecrase le dataset precedent.\n\n" .
                    "Anciens dossiers supprimes: {$existingCount}\n" .
                    "Nouveaux dossiers importes: {$count}\n" .
                    "Annee cible: {$targetYear}\n"
                );
            }

            $this->audit($request, 'import_dossiers', 'dossier', null, 'count=' . $count);
            return response()->json([
                'success' => true,
                'message' => "Import termine: {$count} dossiers",
                'mail_status' => $overwriteMailStatus,
                'logs' => ['Import Laravel OK'],
            ]);
        }

        $preview = $this->simulateImportPreview($mapped, $targetYear);

        return response()->json([
            'success' => true,
            'dossiers' => $preview,
            'existing_count' => (int) DB::table('dossiers')->count(),
        ]);
    }
    public function createSchedule(Request $request): RedirectResponse
    {
        DB::table('scheduled_exports')->insert([
            'name' => (string) $request->input('name', 'Export planifie'),
            'export_type' => (string) $request->input('export_type', 'users'),
            'export_format' => (string) $request->input('export_format', 'csv'),
            'frequency' => (string) $request->input('frequency', 'daily'),
            'weekday' => $request->input('weekday') !== null ? (int) $request->input('weekday') : null,
            'run_at' => (string) $request->input('run_at', '09:00'),
            'recipient_email' => (string) $request->input('recipient_email', ''),
            'is_active' => true,
            'next_run_at' => now()->addDay(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit($request, 'create_schedule', 'scheduled_export');
        return redirect()->route('admin.dashboard');
    }

    public function deleteSchedule(Request $request): RedirectResponse
    {
        $id = (int) $request->input('id');
        DB::table('scheduled_exports')->where('id', $id)->delete();
        $this->audit($request, 'delete_schedule', 'scheduled_export', (string) $id);
        return redirect()->route('admin.dashboard');
    }

    public function runSchedule(Request $request): RedirectResponse
    {
        $id = (int) $request->input('id');
        $s = DB::table('scheduled_exports')->where('id', $id)->first();
        if (! $s) return redirect()->route('admin.dashboard');

        [$title, $headers, $rows] = $this->buildExportDataset((string) $s->export_type, null);
        $csv = $this->buildCsvString($headers, $rows);

        Mail::raw("Export planifie: {$title}", function ($message) use ($s, $csv, $title): void {
            $message->to((string) $s->recipient_email)
                ->subject("Export planifie - {$title}")
                ->attachData($csv, 'export_' . strtolower(str_replace(' ', '_', $title)) . '.csv', ['mime' => 'text/csv']);
        });

        DB::table('scheduled_exports')->where('id', $id)->update(['last_run_at' => now(), 'updated_at' => now()]);
        $this->audit($request, 'run_schedule', 'scheduled_export', (string) $id);
        return redirect()->route('admin.dashboard');
    }

    public function export(Request $request)
    {
        [$title, $headers, $rows] = $this->buildExportDataset((string) $request->input('export_type', 'users'), $request->input('user_id'));
        $format = strtolower((string) $request->input('export_format', 'csv'));

        $this->audit($request, 'export', 'export', null, "type={$request->input('export_type')};format={$format}");

        return match ($format) {
            'xlsx' => $this->exportXlsx($title, $headers, $rows),
            'pdf' => $this->exportPdf($title, $headers, $rows),
            default => $this->exportCsv($title, $headers, $rows),
        };
    }

    private function buildExportDataset(string $type, mixed $userId = null): array
    {
        return match ($type) {
            'planning_global' => $this->datasetPlanningGlobal(),
            'planning_par_personne' => $this->datasetPlanningParPersonne($userId),
            'stats' => $this->datasetStats(),
            'dossiers' => $this->datasetDossiers(),
            default => $this->datasetUsers(),
        };
    }

    private function datasetUsers(): array
    {
        $headers = ['Code', 'Nom', 'Prenom', 'Email', 'Role', 'Actif'];
        $rows = DB::table('utilisateurs')->orderBy('nom')->get(['code_utilisateur', 'nom', 'prenom', 'email', 'role', 'actif'])
            ->map(fn($r) => [(string) $r->code_utilisateur, (string) $r->nom, (string) $r->prenom, (string) $r->email, (string) $r->role, $r->actif ? 'Oui' : 'Non'])->all();
        return ['Utilisateurs', $headers, $rows];
    }

    private function datasetPlanningGlobal(): array
    {
        $headers = ['Code dossier', 'Client', 'Collaborateur', 'Role', 'Debut', 'Fin', 'Statut'];
        $rows = DB::table('affectation as a')->join('dossiers as d', 'd.id', '=', 'a.dossier_id')->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')
            ->orderBy('d.deadline')
            ->get(['d.code_dossier', 'd.nom_client', DB::raw("COALESCE(u.prenom,'') || ' ' || u.nom as collaborateur"), 'u.role', 'd.date_debut', 'd.date_fin', 'd.statut'])
            ->map(fn($r) => [(string) $r->code_dossier, (string) $r->nom_client, trim((string) $r->collaborateur), (string) $r->role, (string) $r->date_debut, (string) $r->date_fin, (string) $r->statut])->all();
        return ['Planning global', $headers, $rows];
    }

    private function datasetPlanningParPersonne(mixed $userId): array
    {
        $query = DB::table('affectation as a')->join('dossiers as d', 'd.id', '=', 'a.dossier_id')->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')->orderBy('u.nom')->orderBy('d.deadline');
        if (! empty($userId)) $query->where('u.id', (int) $userId);

        $headers = ['Collaborateur', 'Code dossier', 'Client', 'Role', 'Debut', 'Fin', 'Commentaires'];
        $rows = $query->get([DB::raw("COALESCE(u.prenom,'') || ' ' || u.nom as collaborateur"), 'd.code_dossier', 'd.nom_client', 'u.role', 'd.date_debut', 'd.date_fin', 'a.commentaires'])
            ->map(fn($r) => [trim((string) $r->collaborateur), (string) $r->code_dossier, (string) $r->nom_client, (string) $r->role, (string) $r->date_debut, (string) $r->date_fin, (string) ($r->commentaires ?? '')])->all();
        return ['Planning par personne', $headers, $rows];
    }

    private function datasetStats(): array
    {
        $headers = ['Categorie', 'Valeur'];
        $rows = [
            ['Utilisateurs total', (string) DB::table('utilisateurs')->count()],
            ['Utilisateurs actifs', (string) DB::table('utilisateurs')->where('actif', true)->count()],
            ['Utilisateurs inactifs', (string) DB::table('utilisateurs')->where('actif', false)->count()],
            ['Dossiers total', (string) DB::table('dossiers')->count()],
            ['Dossiers en retard', (string) DB::table('dossiers')->where('statut', 'declarer_en_retard')->count()],
        ];
        return ['Statistiques', $headers, $rows];
    }

    private function datasetDossiers(): array
    {
        $headers = ['Code dossier', 'Client', 'Debut', 'Fin', 'Deadline', 'Statut', 'Heures prevues'];
        $rows = DB::table('dossiers')->orderBy('deadline')->get(['code_dossier', 'nom_client', 'date_debut', 'date_fin', 'deadline', 'statut', 'heure_prevues'])
            ->map(fn($r) => [(string) $r->code_dossier, (string) $r->nom_client, (string) $r->date_debut, (string) $r->date_fin, (string) $r->deadline, (string) $r->statut, (string) $r->heure_prevues])->all();
        return ['Dossiers', $headers, $rows];
    }

    private function buildCsvString(array $headers, array $rows): string
    {
        $fp = fopen('php://temp', 'rb+');
        fputcsv($fp, $headers, ';');
        foreach ($rows as $row) fputcsv($fp, $row, ';');
        rewind($fp);
        $content = stream_get_contents($fp);
        fclose($fp);
        return (string) $content;
    }

    private function exportCsv(string $title, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $out = fopen('php://output', 'wb');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) fputcsv($out, $row, ';');
            fclose($out);
        }, 'export_' . str_replace(' ', '_', strtolower($title)) . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportXlsx(string $title, array $headers, array $rows)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(substr($title, 0, 31));
        $sheet->fromArray($headers, null, 'A1');
        if (!empty($rows)) $sheet->fromArray($rows, null, 'A2');

        $writer = new Xlsx($spreadsheet);
        return response()->streamDownload(fn() => $writer->save('php://output'), 'export_' . str_replace(' ', '_', strtolower($title)) . '.xlsx', ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function exportPdf(string $title, array $headers, array $rows)
    {
        $logoPath = public_path('assets/images/logo-MGB.png');
        $logoDataUri = '';
        if (is_file($logoPath)) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode((string) file_get_contents($logoPath));
        }

        $thead = '<tr>' . implode('', array_map(fn($h) => '<th style="border:1px solid #ddd;padding:6px;background:#f2f2f2;">' . e($h) . '</th>', $headers)) . '</tr>';
        $tbody = '';
        foreach ($rows as $row) $tbody .= '<tr>' . implode('', array_map(fn($c) => '<td style="border:1px solid #ddd;padding:6px;">' . e((string) $c) . '</td>', $row)) . '</tr>';
        $html = '
            <div style="font-family:DejaVu Sans, sans-serif;">
                <table style="width:100%;margin-bottom:14px;">
                    <tr>
                        <td style="width:120px;">' . ($logoDataUri !== '' ? '<img src="' . $logoDataUri . '" style="height:58px;" />' : '') . '</td>
                        <td style="text-align:right;">
                            <div style="font-size:20px;color:#003366;font-weight:700;">MG EXPERTISE</div>
                            <div style="font-size:12px;color:#666;">' . e($title) . '</div>
                            <div style="font-size:10px;color:#999;">Généré le ' . e(now()->format('d/m/Y H:i')) . '</div>
                        </td>
                    </tr>
                </table>
                <table style="border-collapse:collapse;width:100%;font-size:11px;">
                    <thead>' . $thead . '</thead>
                    <tbody>' . $tbody . '</tbody>
                </table>
            </div>';
        return Pdf::loadHTML($html)->download('export_' . str_replace(' ', '_', strtolower($title)) . '.pdf');
    }

    public function usersFragment(): JsonResponse
    {
        return response()->json([
            'success' => true,
            ...$this->usersFragments(),
        ]);
    }

    private function usersFragments(): array
    {
        $users = DB::table('utilisateurs')
            ->select('id', 'code_utilisateur', 'nom', 'prenom', 'email', 'role', 'actif')
            ->orderBy('nom')
            ->get()
            ->map(fn($r) => (array) $r)
            ->all();

        return [
            'users_rows_html' => view('admin.partials.users_rows', ['users' => $users])->render(),
            'users_select_html' => view('admin.partials.user_options', ['users' => $users])->render(),
        ];
    }

    private function registerRoleCandidate(array &$roleByCode, string $code, string $candidateRole): void
    {
        $userCode = trim($code);
        if ($userCode === '') {
            return;
        }

        $role = $this->normalizeRole($candidateRole);
        $current = $roleByCode[$userCode] ?? null;
        if ($current === null || $this->rolePriority($role) > $this->rolePriority($current)) {
            $roleByCode[$userCode] = $role;
        }
    }

    private function normalizeRole(string $role): string
    {
        $r = strtolower(trim($role));
        return match ($r) {
            'associe', 'associé' => 'associe',
            'chef', 'chef_de_mission', 'cdm' => 'chef',
            default => 'collaborateur',
        };
    }

    private function rolePriority(string $role): int
    {
        return match ($this->normalizeRole($role)) {
            'associe' => 3,
            'chef' => 2,
            default => 1,
        };
    }

    /**
     * @param array<string,mixed> $dossier
     * @param array<string,array<string,mixed>> $participants
     * @param array<string,mixed> $planningState
     * @param int $totalDossiers
     * @return array{0:string,1:string,2:array<string,float>}
     */
    private function computeImportPlanning(array $dossier, array &$participants, array &$planningState, int $targetYear, int $totalDossiers = 1): array
    {
        $today = Carbon::today();
        $receptionDate = $this->safeCarbonDate($dossier['date_reception'] ?? null) ?? $today->copy();
        $inputStart = $this->safeCarbonDate($dossier['date_debut'] ?? null);
        $baseStart = $inputStart ? $inputStart->copy() : $receptionDate->copy();
        $windowStart = Carbon::create($targetYear, 1, 15)->startOfDay(); // mi-janvier
        $windowHardEnd = Carbon::create($targetYear, 7, 7)->startOfDay(); // debut juillet max
        $windowDays = max(1, $windowStart->diffInDays($windowHardEnd));
        $slotIndex = (int) ($planningState['slot_index'] ?? 0);
        $slotSpacing = max(1, (int) floor($windowDays / max(1, $totalDossiers)));

        $hoursTotal = max(1.0, (float) ($dossier['heure_prevues'] ?? 0.0));
        $durationDays = max(1, (int) ceil($hoursTotal / 8.0));

        $candidateStart = $baseStart->copy();
        if ($candidateStart->lessThan($windowStart)) {
            $candidateStart = $windowStart->copy();
        }

        $targetSlotStart = $windowStart->copy()->addDays(min($windowDays, $slotIndex * $slotSpacing));
        if ($targetSlotStart->lessThan($candidateStart)) {
            $targetSlotStart = $candidateStart->copy();
        }

        $userLoads = (array) ($planningState['user_loads'] ?? []);
        $currentLoad = 0.0;
        $currentLoadWeight = 0.0;
        foreach ($participants as $participant) {
            $userId = (int) ($participant['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $roleRatio = (float) ($participant['ratio'] ?? 0.0);
            if ($roleRatio <= 0) {
                continue;
            }
            $currentLoad += ((float) ($userLoads[$userId] ?? 0.0)) * $roleRatio;
            $currentLoadWeight += $roleRatio;
        }
        if ($currentLoadWeight > 0) {
            $currentLoad /= $currentLoadWeight;
        }

        $averageLoad = 0.0;
        $loadCount = count($userLoads);
        if ($loadCount > 0) {
            $averageLoad = array_sum($userLoads) / $loadCount;
        }
        $loadBiasDays = 0;
        if ($currentLoad > $averageLoad) {
            $loadBiasDays = min(10, (int) ceil(($currentLoad - $averageLoad) / 16.0));
        }
        if ($loadBiasDays > 0) {
            $targetSlotStart->addDays($loadBiasDays);
        }

        if ($targetSlotStart->greaterThan($candidateStart)) {
            $candidateStart = $targetSlotStart->copy();
        }

        foreach ($participants as $participant) {
            $userId = (int) ($participant['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $availableFrom = $planningState['availability'][$userId] ?? null;
            if ($availableFrom instanceof Carbon && $availableFrom->greaterThan($candidateStart)) {
                $candidateStart = $availableFrom->copy();
            }
        }

        $plannedStart = $candidateStart->copy();
        if ($plannedStart->greaterThan($windowHardEnd)) {
            $plannedStart = $windowHardEnd->copy();
        }
        $plannedEnd = $plannedStart->copy()->addDays($durationDays - 1);
        if ($plannedEnd->greaterThan($windowHardEnd)) {
            $plannedEnd = $windowHardEnd->copy();
        }

        $sharesByRole = $this->computeRoleShares($hoursTotal, $participants);

        foreach ($participants as $participant) {
            $userId = (int) ($participant['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $planningState['availability'][$userId] = $plannedEnd->copy()->addDay();
        }

        foreach ($participants as $participantKey => $participant) {
            $userId = (int) ($participant['user_id'] ?? 0);
            if ($userId <= 0) {
                continue;
            }
            $roleShare = (float) ($sharesByRole[$participantKey] ?? 0.0);
            $planningState['user_loads'][$userId] = (float) ($userLoads[$userId] ?? 0.0) + $roleShare;
        }
        $planningState['slot_index'] = $slotIndex + 1;

        return [$plannedStart->toDateString(), $plannedEnd->toDateString(), $sharesByRole];
    }

    /**
     * @param array<string,array<string,mixed>> $participants
     * @return array<string,float>
     */
    private function computeRoleShares(float $hoursTotal, array $participants): array
    {
        $baseRatios = [];
        foreach ($participants as $role => $participant) {
            $userId = (int) ($participant['user_id'] ?? 0);
            if ($userId > 0) {
                $baseRatios[$role] = (float) ($participant['ratio'] ?? 0.0);
            }
        }

        if ($baseRatios === []) {
            return [
                'collaborateur' => round($hoursTotal, 2),
                'chef' => 0.0,
                'associe' => 0.0,
            ];
        }

        $ratioSum = array_sum($baseRatios);
        if ($ratioSum <= 0.0) {
            $ratioSum = 1.0;
        }

        $shares = ['collaborateur' => 0.0, 'chef' => 0.0, 'associe' => 0.0];
        $allocated = 0.0;
        $roles = array_keys($baseRatios);
        $lastRole = end($roles) ?: 'collaborateur';

        foreach ($baseRatios as $role => $ratio) {
            $value = round(($hoursTotal * $ratio) / $ratioSum, 2);
            $shares[$role] = $value;
            $allocated += $value;
        }

        $delta = round($hoursTotal - $allocated, 2);
        $shares[$lastRole] = round(($shares[$lastRole] ?? 0.0) + $delta, 2);

        return $shares;
    }

    private function storeImportVentilation(int $dossierId, int $userId, float $hours, string $plannedStart): void
    {
        if ($hours <= 0.0) {
            return;
        }

        $start = $this->safeCarbonDate($plannedStart) ?? Carbon::today();
        $monthFieldMap = [
            1 => 'janvier',
            2 => 'fevrier',
            3 => 'mars',
            4 => 'avril',
            5 => 'mai',
        ];

        if (DB::getSchemaBuilder()->hasTable('ventilation')) {
            $monthIndex = (int) $start->month;
            $monthField = $monthFieldMap[$monthIndex] ?? 'mai';
            $payload = [
                'janvier' => 0.0,
                'fevrier' => 0.0,
                'mars' => 0.0,
                'avril' => 0.0,
                'mai' => 0.0,
                'updated_at' => now(),
                'created_at' => now(),
            ];
            $payload[$monthField] = round($hours, 2);

            DB::table('ventilation')->updateOrInsert(
                ['dossier_id' => $dossierId, 'collaborateur_id' => $userId],
                $payload
            );
        }

        if (DB::getSchemaBuilder()->hasTable('ventilation_entries')) {
            DB::table('ventilation_entries')->insert([
                'dossier_id' => $dossierId,
                'collaborateur_id' => $userId,
                'periode' => $start->toDateString(),
                'competence' => 'import_auto',
                'heures' => round($hours, 2),
                'justification' => 'Import annuel avec ventilation automatique 70/20/10',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Simulation avant import: calcule dates/ventilation sans ecriture en base.
     *
     * @param array<int,array<string,mixed>> $mapped
     * @return array<int,array<string,mixed>>
     */
    private function simulateImportPreview(array $mapped, int $targetYear): array
    {
        $preview = [];
        $planningState = ['availability' => [], 'user_loads' => [], 'slot_index' => 0];
        $totalCount = max(1, count($mapped));

        foreach ($mapped as $d) {
            if ((string) ($d['code_dossier'] ?? '') === '') {
                continue;
            }

            $participants = [
                'collaborateur' => [
                    'code' => trim((string) ($d['collab'] ?? '')),
                    'label' => 'collaborateur',
                    'ratio' => 0.70,
                ],
                'chef' => [
                    'code' => trim((string) ($d['cdm'] ?? '')),
                    'label' => 'chef_de_mission',
                    'ratio' => 0.20,
                ],
                'associe' => [
                    'code' => trim((string) ($d['associe'] ?? '')),
                    'label' => 'associe',
                    'ratio' => 0.10,
                ],
            ];

            foreach ($participants as $key => $participant) {
                // En simulation, on utilise le code comme identifiant de charge.
                $participants[$key]['user_id'] = $participant['code'] !== '' ? crc32($participant['code']) : 0;
            }

            [$plannedStart, $plannedEnd, $sharesByRole] = $this->computeImportPlanning($d, $participants, $planningState, $targetYear, $totalCount);

            $resumeParts = [];
            if ($participants['collaborateur']['code'] !== '' && (float) ($sharesByRole['collaborateur'] ?? 0.0) > 0) {
                $resumeParts[] = 'Collab ' . number_format((float) $sharesByRole['collaborateur'], 2, '.', '') . 'h';
            }
            if ($participants['chef']['code'] !== '' && (float) ($sharesByRole['chef'] ?? 0.0) > 0) {
                $resumeParts[] = 'CDM ' . number_format((float) $sharesByRole['chef'], 2, '.', '') . 'h';
            }
            if ($participants['associe']['code'] !== '' && (float) ($sharesByRole['associe'] ?? 0.0) > 0) {
                $resumeParts[] = 'Associe ' . number_format((float) $sharesByRole['associe'], 2, '.', '') . 'h';
            }

            $d['date_debut'] = $plannedStart;
            $d['date_fin'] = $plannedEnd;
            $d['ventilation_resume'] = implode(' | ', $resumeParts);
            $preview[] = $d;
        }

        return $preview;
    }

    /**
     * Sends a simple notification email and returns the delivery status.
     */
    private function sendNotificationMail(?string $email, string $subject, string $body): string
    {
        $email = trim((string) $email);
        if ($email === '') {
            return 'not_sent';
        }

        try {
            Mail::raw($body, function ($message) use ($email, $subject): void {
                $message->to($email)->subject($subject);
            });

            return 'sent';
        } catch (\Throwable) {
            return 'failed';
        }
    }

    /**
     * Returns the email address for a user id, if available.
     */
    private function userEmailById(int $userId): string
    {
        if ($userId <= 0) {
            return '';
        }

        return (string) DB::table('utilisateurs')->where('id', $userId)->value('email');
    }

    private function safeCarbonDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function audit(Request $request, string $action, ?string $targetType = null, ?string $targetId = null, ?string $message = null, array $meta = []): void
    {
        if (!DB::getSchemaBuilder()->hasTable('audit_logs')) return;

        DB::table('audit_logs')->insert([
            'user_id' => session('user_id'),
            'user_code' => session('user_nom'),
            'role' => session('user_role'),
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'message' => $message,
            'meta' => !empty($meta) ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'ip' => (string) $request->ip(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

