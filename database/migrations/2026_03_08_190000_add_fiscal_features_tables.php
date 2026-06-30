<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dossiers', function (Blueprint $table): void {
            if (! Schema::hasColumn('dossiers', 'workflow_step')) {
                $table->string('workflow_step', 50)->default('reception')->after('statut');
            }
            if (! Schema::hasColumn('dossiers', 'pieces_critiques_ok')) {
                $table->boolean('pieces_critiques_ok')->default(false)->after('workflow_step');
            }
            if (! Schema::hasColumn('dossiers', 'portefeuille')) {
                $table->string('portefeuille', 120)->nullable()->after('pieces_critiques_ok');
            }
            if (! Schema::hasColumn('dossiers', 'type_dossier')) {
                $table->string('type_dossier', 50)->default('liasse')->after('portefeuille');
            }
            if (! Schema::hasColumn('dossiers', 'sla_heures')) {
                $table->integer('sla_heures')->default(48)->after('type_dossier');
            }
        });

        if (! Schema::hasTable('dossier_checklists')) {
            Schema::create('dossier_checklists', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
                $table->string('category', 40);
                $table->string('item_label', 190);
                $table->boolean('is_required')->default(true);
                $table->boolean('is_done')->default(false);
                $table->foreignId('done_by')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->timestamp('done_at')->nullable();
                $table->timestamps();
                $table->unique(['dossier_id', 'category', 'item_label']);
            });
        }

        if (! Schema::hasTable('internal_alerts')) {
            Schema::create('internal_alerts', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('dossier_id')->nullable()->constrained('dossiers')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->string('level', 20)->default('info');
                $table->string('title', 160);
                $table->text('message')->nullable();
                $table->string('status', 20)->default('open');
                $table->timestamp('due_at')->nullable();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();
                $table->index(['status', 'due_at']);
            });
        }

        if (! Schema::hasTable('ventilation_entries')) {
            Schema::create('ventilation_entries', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
                $table->foreignId('collaborateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->date('periode');
                $table->string('competence', 80)->default('general');
                $table->decimal('heures', 10, 2)->default(0);
                $table->text('justification')->nullable();
                $table->timestamps();
                $table->index(['dossier_id', 'periode']);
            });
        }

        if (! Schema::hasTable('ventilation_history')) {
            Schema::create('ventilation_history', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('dossier_id')->constrained('dossiers')->cascadeOnDelete();
                $table->foreignId('collaborateur_id')->constrained('utilisateurs')->cascadeOnDelete();
                $table->string('mois', 20);
                $table->decimal('old_value', 10, 2)->default(0);
                $table->decimal('new_value', 10, 2)->default(0);
                $table->decimal('delta', 10, 2)->default(0);
                $table->text('justification')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('utilisateurs')->nullOnDelete();
                $table->timestamps();
                $table->index(['dossier_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('ventilation_history')) {
            Schema::drop('ventilation_history');
        }
        if (Schema::hasTable('ventilation_entries')) {
            Schema::drop('ventilation_entries');
        }
        if (Schema::hasTable('internal_alerts')) {
            Schema::drop('internal_alerts');
        }
        if (Schema::hasTable('dossier_checklists')) {
            Schema::drop('dossier_checklists');
        }

        Schema::table('dossiers', function (Blueprint $table): void {
            foreach (['sla_heures', 'type_dossier', 'portefeuille', 'pieces_critiques_ok', 'workflow_step'] as $col) {
                if (Schema::hasColumn('dossiers', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
