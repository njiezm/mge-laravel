<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('utilisateurs', function (Blueprint $table): void {
            $table->id();
            $table->string('code_utilisateur', 100)->unique();
            $table->string('nom', 120);
            $table->string('prenom', 120)->nullable();
            $table->string('email')->unique();
            $table->string('mot_de_passe');
            $table->string('role', 50)->default('Collaborateur');
            $table->boolean('actif')->default(true);
            $table->boolean('premiere_connexion')->default(true);
            $table->timestamps();
        });

        Schema::create('dossiers', function (Blueprint $table): void {
            $table->id();
            $table->string('code_dossier', 100)->unique();
            $table->string('nom_client');
            $table->date('deadline')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->string('statut', 80)->default('non_traite');
            $table->timestamps();
        });

        Schema::create('affectation', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('utilisateur_id')->constrained('utilisateurs')->cascadeOnDelete();
            $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
            $table->text('commentaires')->nullable();
            $table->timestamps();

            $table->unique(['utilisateur_id', 'dossier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affectation');
        Schema::dropIfExists('dossiers');
        Schema::dropIfExists('utilisateurs');
    }
};
