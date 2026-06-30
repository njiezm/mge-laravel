<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class RunScheduledExports extends Command
{
    protected $signature = 'exports:run-scheduled';
    protected $description = 'Run active scheduled exports and send by email';

    public function handle(): int
    {
        if (!DB::getSchemaBuilder()->hasTable('scheduled_exports')) {
            $this->warn('scheduled_exports table missing.');
            return self::SUCCESS;
        }

        $now = now();
        $schedules = DB::table('scheduled_exports')->where('is_active', true)->get();

        foreach ($schedules as $s) {
            if (!$this->isDue($s, $now)) {
                continue;
            }

            [$title, $headers, $rows] = $this->dataset((string) $s->export_type);
            $csv = $this->toCsv($headers, $rows);

            Mail::raw("Export planifie: {$title}", function ($message) use ($s, $csv, $title): void {
                $message->to((string) $s->recipient_email)
                    ->subject("Export planifie - {$title}")
                    ->attachData($csv, 'export_' . strtolower(str_replace(' ', '_', $title)) . '.csv', ['mime' => 'text/csv']);
            });

            DB::table('scheduled_exports')->where('id', $s->id)->update([
                'last_run_at' => $now,
                'next_run_at' => $this->nextRun($s, $now),
                'updated_at' => $now,
            ]);

            DB::table('audit_logs')->insert([
                'user_id' => null,
                'user_code' => 'system',
                'role' => 'system',
                'action' => 'scheduled_export_run',
                'target_type' => 'scheduled_export',
                'target_id' => (string) $s->id,
                'message' => "Export {$title} envoye a {$s->recipient_email}",
                'meta' => json_encode(['export_type' => $s->export_type], JSON_UNESCAPED_UNICODE),
                'ip' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            $this->info("Schedule #{$s->id} sent.");
        }

        return self::SUCCESS;
    }

    private function isDue(object $s, $now): bool
    {
        $timeNow = $now->format('H:i');
        if ($timeNow < (string) $s->run_at) return false;

        if ($s->frequency === 'weekly') {
            $weekday = (int) ($s->weekday ?? 1);
            return ((int) $now->dayOfWeekIso) === $weekday;
        }

        return true;
    }

    private function nextRun(object $s, $now): string
    {
        if ($s->frequency === 'weekly') {
            return $now->copy()->addWeek()->toDateTimeString();
        }
        return $now->copy()->addDay()->toDateTimeString();
    }

    private function dataset(string $type): array
    {
        return match ($type) {
            'stats' => $this->stats(),
            'dossiers' => $this->dossiers(),
            'planning_global' => $this->planningGlobal(),
            default => $this->users(),
        };
    }

    private function users(): array
    {
        $headers = ['Code', 'Nom', 'Prenom', 'Email', 'Role', 'Actif'];
        $rows = DB::table('utilisateurs')->orderBy('nom')->get(['code_utilisateur', 'nom', 'prenom', 'email', 'role', 'actif'])
            ->map(fn($r) => [(string) $r->code_utilisateur, (string) $r->nom, (string) $r->prenom, (string) $r->email, (string) $r->role, $r->actif ? 'Oui' : 'Non'])->all();
        return ['Utilisateurs', $headers, $rows];
    }

    private function planningGlobal(): array
    {
        $headers = ['Code dossier', 'Client', 'Collaborateur', 'Role', 'Debut', 'Fin', 'Statut'];
        $rows = DB::table('affectation as a')->join('dossiers as d', 'd.id', '=', 'a.dossier_id')->join('utilisateurs as u', 'u.id', '=', 'a.utilisateur_id')
            ->orderBy('d.deadline')
            ->get(['d.code_dossier', 'd.nom_client', DB::raw("COALESCE(u.prenom,'') || ' ' || u.nom as collaborateur"), 'u.role', 'd.date_debut', 'd.date_fin', 'd.statut'])
            ->map(fn($r) => [(string) $r->code_dossier, (string) $r->nom_client, trim((string) $r->collaborateur), (string) $r->role, (string) $r->date_debut, (string) $r->date_fin, (string) $r->statut])->all();
        return ['Planning global', $headers, $rows];
    }

    private function dossiers(): array
    {
        $headers = ['Code dossier', 'Client', 'Debut', 'Fin', 'Deadline', 'Statut', 'Heures prevues'];
        $rows = DB::table('dossiers')->orderBy('deadline')->get(['code_dossier', 'nom_client', 'date_debut', 'date_fin', 'deadline', 'statut', 'heure_prevues'])
            ->map(fn($r) => [(string) $r->code_dossier, (string) $r->nom_client, (string) $r->date_debut, (string) $r->date_fin, (string) $r->deadline, (string) $r->statut, (string) $r->heure_prevues])->all();
        return ['Dossiers', $headers, $rows];
    }

    private function stats(): array
    {
        $headers = ['Categorie', 'Valeur'];
        $rows = [
            ['Utilisateurs total', (string) DB::table('utilisateurs')->count()],
            ['Utilisateurs actifs', (string) DB::table('utilisateurs')->where('actif', true)->count()],
            ['Dossiers total', (string) DB::table('dossiers')->count()],
            ['Dossiers en retard', (string) DB::table('dossiers')->where('statut', 'declarer_en_retard')->count()],
        ];
        return ['Statistiques', $headers, $rows];
    }

    private function toCsv(array $headers, array $rows): string
    {
        $fp = fopen('php://temp', 'rb+');
        fputcsv($fp, $headers, ';');
        foreach ($rows as $row) fputcsv($fp, $row, ';');
        rewind($fp);
        $out = stream_get_contents($fp);
        fclose($fp);
        return (string) $out;
    }
}
