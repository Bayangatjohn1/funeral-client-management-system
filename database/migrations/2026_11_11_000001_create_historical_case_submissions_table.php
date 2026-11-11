<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('historical_case_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('submission_code', 32)->unique();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('encoded_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('authorized_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('official_case_id')->nullable()->constrained('funeral_cases')->nullOnDelete();
            $table->foreignId('parent_submission_id')->nullable()->constrained('historical_case_submissions')->nullOnDelete();
            $table->string('status', 24)->default('PENDING_APPROVAL')->index();
            $table->json('submission_data');
            $table->text('review_note')->nullable();
            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('resubmitted_at')->nullable();
            $table->timestamps();
            $table->index(['branch_id', 'status', 'created_at']);
            $table->index(['encoded_by', 'status']);
        });

        Schema::create('historical_case_submission_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('historical_case_submission_id');
            $table->foreign('historical_case_submission_id', 'hcsr_submission_fk')
                ->references('id')->on('historical_case_submissions')->cascadeOnDelete();
            $table->foreignId('reviewed_by');
            $table->foreign('reviewed_by', 'hcsr_reviewer_fk')
                ->references('id')->on('users')->restrictOnDelete();
            $table->string('action', 24);
            $table->text('note')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['historical_case_submission_id', 'created_at'], 'historical_review_history_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historical_case_submission_reviews');
        Schema::dropIfExists('historical_case_submissions');
    }
};
