<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AffectationSeeder extends Seeder
{
    public function run(): void
    {
        $utilisateurs = DB::table('utilisateurs')
            ->where('role', '!=', 'admin')
            ->pluck('id')
            ->values();

        $dossiers = DB::table('dossiers')->pluck('id')->values();

        if ($utilisateurs->isEmpty() || $dossiers->isEmpty()) {
            return;
        }

        foreach ($dossiers as $index => $dossierId) {
            $userId = $utilisateurs[$index % $utilisateurs->count()];

            DB::table('affectation')->updateOrInsert(
                [
                    'utilisateur_id' => $userId,
                    'dossier_id' => $dossierId,
                ],
                [
                    'commentaires' => 'Affectation seed initiale',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }
}