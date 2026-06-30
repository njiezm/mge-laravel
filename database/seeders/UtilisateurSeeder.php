<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UtilisateurSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'code_utilisateur' => 'ADMIN01',
                'nom' => 'Admin',
                'prenom' => 'MG',
                'email' => 'admin@mgexp.local',
                'mot_de_passe' => Hash::make('Admin@12345'),
                'role' => 'admin',
                'actif' => true,
                'premiere_connexion' => false,
            ],
            [
                'code_utilisateur' => 'ASSOCIE01',
                'nom' => 'Leroux',
                'prenom' => 'Sophie',
                'email' => 'associe@mgexp.local',
                'mot_de_passe' => Hash::make('Associe@12345'),
                'role' => 'associe',
                'actif' => true,
                'premiere_connexion' => false,
            ],
            [
                'code_utilisateur' => 'COLLAB01',
                'nom' => 'Dupont',
                'prenom' => 'Alice',
                'email' => 'alice@mgexp.local',
                'mot_de_passe' => Hash::make('User@12345'),
                'role' => 'Collaborateur',
                'actif' => true,
                'premiere_connexion' => false,
            ],
            [
                'code_utilisateur' => 'COLLAB02',
                'nom' => 'Martin',
                'prenom' => 'Bruno',
                'email' => 'bruno@mgexp.local',
                'mot_de_passe' => Hash::make('User@12345'),
                'role' => 'Collaborateur',
                'actif' => true,
                'premiere_connexion' => false,
            ],
            [
                'code_utilisateur' => 'COLLAB03',
                'nom' => 'Bernard',
                'prenom' => 'Claire',
                'email' => 'claire@mgexp.local',
                'mot_de_passe' => Hash::make('User@12345'),
                'role' => 'Chef de mission',
                'actif' => true,
                'premiere_connexion' => false,
            ],
            [
                'code_utilisateur' => 'COLLAB04',
                'nom' => 'Petit',
                'prenom' => 'David',
                'email' => 'david@mgexp.local',
                'mot_de_passe' => Hash::make('User@12345'),
                'role' => 'Collaborateur',
                'actif' => true,
                'premiere_connexion' => true,
            ],
        ];

        foreach ($users as $user) {
            DB::table('utilisateurs')->updateOrInsert(
                ['email' => $user['email']],
                array_merge($user, [
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
            );
        }
    }
}
