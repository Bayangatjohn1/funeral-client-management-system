<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30)->default('manual');
            $table->string('status', 30)->default('pending');
            $table->string('disk')->default('local');
            $table->string('path')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->string('checksum', 64)->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
            $table->index('expires_at');
        });

        Schema::create('restore_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('system_backup_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('pending_validation');
            $table->text('reason');
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('executed_at')->nullable();
            $table->text('failure_message')->nullable();
            $table->json('validation_results')->nullable();
            $table->timestamps();
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->timestamp('completed_at')->nullable()->after('case_status');
            $table->date('retention_end_date')->nullable()->after('completed_at')->index();
            $table->string('retention_status', 30)->default('retained')->after('retention_end_date')->index();
            $table->timestamp('legal_hold_at')->nullable()->after('retention_status');
            $table->foreignId('legal_hold_by')->nullable()->after('legal_hold_at')->constrained('users')->nullOnDelete();
            $table->text('legal_hold_reason')->nullable()->after('legal_hold_by');
            $table->timestamp('legal_hold_released_at')->nullable()->after('legal_hold_reason');
            $table->foreignId('legal_hold_released_by')->nullable()->after('legal_hold_released_at')->constrained('users')->nullOnDelete();
        });

        Schema::create('disposal_ledger', function (Blueprint $table) {
            $table->id();
            $table->uuid('disposal_reference')->unique();
            $table->string('case_reference_hash', 64)->index();
            $table->date('retention_end_date');
            $table->timestamp('disposed_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('policy_version', 50);
            $table->text('reason');
            $table->json('verification')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('replaces_payment_id')->nullable()->after('funeral_case_id')->constrained('payments')->nullOnDelete();
            $table->index(['status', 'paid_at']);
        });

        Schema::create('payment_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('correction_type', 30);
            $table->string('status', 20)->default('PENDING');
            $table->json('original_values');
            $table->json('proposed_values')->nullable();
            $table->text('reason');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->foreignId('replacement_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->timestamps();
            $table->index(['branch_id', 'status']);
            $table->index(['payment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_correction_requests');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('replaces_payment_id');
            $table->dropIndex(['status', 'paid_at']);
        });
        Schema::dropIfExists('disposal_ledger');
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('legal_hold_released_by');
            $table->dropConstrainedForeignId('legal_hold_by');
            $table->dropColumn(['completed_at', 'retention_end_date', 'retention_status', 'legal_hold_at', 'legal_hold_reason', 'legal_hold_released_at']);
        });
        Schema::dropIfExists('restore_requests');
        Schema::dropIfExists('system_backups');
    }
};
