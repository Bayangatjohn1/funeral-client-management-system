<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_branches')) {
            Schema::create('user_branches', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('branch_id')->constrained()->restrictOnDelete();
                $table->timestamps();
                $table->unique(['user_id', 'branch_id']);
            });
        }

        if (!Schema::hasTable('case_notes')) {
            Schema::create('case_notes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('funeral_case_id')->constrained()->restrictOnDelete();
                $table->foreignId('author_id')->constrained('users')->restrictOnDelete();
                $table->foreignId('branch_id')->constrained()->restrictOnDelete();
                $table->text('note');
                $table->enum('visibility', ['staff', 'admin', 'owner'])->default('staff');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('audit_logs')) {
            Schema::create('audit_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('action', 120);
                $table->string('entity_type', 120)->nullable();
                $table->unsignedBigInteger('entity_id')->nullable();
                $table->foreignId('branch_id')->nullable()->constrained()->nullOnDelete();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['entity_type', 'entity_id']);
                $table->index('branch_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('case_notes');
        Schema::dropIfExists('user_branches');
    }
};

