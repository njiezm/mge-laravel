<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DossierSeeder extends Seeder
{
    public function run(): void
    {
        $dossiers = [
            ['code_dossier' => 'DOS-2026-001', 'nom_client' => 'C001 - Alpha SARL', 'statut' => 'non_traite'],
            ['code_dossier' => 'DOS-2026-002', 'nom_client' => 'C002 - Beta SAS', 'statut' => 'en_cours_traitement'],
            ['code_dossier' => 'DOS-2026-003', 'nom_client' => 'C003 - Gamma Holding', 'statut' => 'revu_en_cours'],
            ['code_dossier' => 'DOS-2026-004', 'nom_client' => 'C004 - Delta Industrie', 'statut' => 'revu_associe'],
            ['code_dossier' => 'DOS-2026-005', 'nom_client' => 'C005 - Epsilon Conseil', 'statut' => 'liasse_envoyee'],
            ['code_dossier' => 'DOS-2026-006', 'nom_client' => 'C006 - Zeta Retail', 'statut' => 'non_traite'],
            ['code_dossier' => 'DOS-2026-007', 'nom_client' => 'C007 - Eta Services', 'statut' => 'en_cours_traitement'],
            ['code_dossier' => 'DOS-2026-008', 'nom_client' => 'C008 - Theta Tech', 'statut' => 'declarer_en_retard'],
        ];

        $startDate = now()->startOfMonth();

        foreach ($dossiers as $idx => $dossier) {
            $dateDebut = $startDate->copy()->addDays($idx * 3);
            $dateFin = $dateDebut->copy()->addDays(5);

            DB::table('dossiers')->updateOrInsert(
                ['code_dossier' => $dossier['code_dossier']],
                array_merge($dossier, [
                    'deadline' => $dateFin->copy()->addDays(15)->toDateString(),
                    'date_debut' => $dateDebut->toDateString(),
                    'date_fin' => $dateFin->toDateString(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}