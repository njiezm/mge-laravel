<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table): void {
            if (! Schema::hasColumn('dossiers', 'heure_prevues')) {
                $table->decimal('heure_prevues', 10, 2)->default(0)->after('statut');
            }
            if (! Schema::hasColumn('dossiers', 'critere')) {
                $table->integer('critere')->default(0)->after('heure_prevues');
            }
        });

        if (! Schema::hasTable('ventilation')) {
            Schema::create('ventilation', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
                $table->foreignId('collaborateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->decimal('janvier', 8, 2)->default(0);
                $table->decimal('fevrier', 8, 2)->default(0);
                $table->decimal('mars', 8, 2)->default(0);
                $table->decimal('avril', 8, 2)->default(0);
                $table->decimal('mai', 8, 2)->default(0);
                $table->timestamps();
                $table->unique(['dossier_id', 'collaborateur_id']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventilation')) {
            Schema::drop('ventilation');
        }

        Schema::table('dossiers', function (Blueprint $table): void {
            if (Schema::hasColumn('dossiers', 'critere')) {
                $table->dropColumn('critere');
            }
            if (Schema::hasColumn('dossiers', 'heure_prevues')) {
                $table->dropColumn('heure_prevues');
            }
        });
    }
};