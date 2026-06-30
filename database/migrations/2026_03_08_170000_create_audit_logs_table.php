<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('utilisateurs')->nullOnDelete();
            $table->string('user_code', 120)->nullable();
            $table->string('role', 80)->nullable();
            $table->string('action', 120);
            $table->string('target_type', 120)->nullable();
            $table->string('target_id', 120)->nullable();
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->string('ip', 64)->nullable();
            $table->timestamps();
            $table->index(['action', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
