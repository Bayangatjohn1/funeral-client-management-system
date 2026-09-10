<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intake_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('draft_number')->unique();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->json('payload');
            $table->unsignedTinyInteger('current_step')->default(1);
            $table->enum('entry_mode', ['main', 'other'])->default('main');
            $table->enum('status', ['IN_PROGRESS', 'SUBMITTED', 'DISCARDED'])->default('IN_PROGRESS');
            $table->foreignId('submitted_case_id')->nullable()->constrained('funeral_cases')->nullOnDelete();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_by', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('last_saved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intake_drafts');
    }
};
