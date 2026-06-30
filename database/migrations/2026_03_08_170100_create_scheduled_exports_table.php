<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_exports', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('export_type', 80);
            $table->string('export_format', 20)->default('csv');
            $table->string('frequency', 20)->default('daily');
            $table->unsignedTinyInteger('weekday')->nullable();
            $table->string('run_at', 5)->default('09:00');
            $table->string('recipient_email', 190);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_exports');
    }
};
