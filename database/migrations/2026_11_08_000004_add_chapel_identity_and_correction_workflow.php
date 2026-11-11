<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('add_on_catalogs', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('chapel_address')->nullable();
            $table->string('chapel_identity', 180)->nullable()->unique();
        });
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->foreignId('chapel_catalog_id')->nullable()->constrained('add_on_catalogs')->restrictOnDelete();
            $table->json('chapel_schedule_acknowledgment')->nullable();
        });
        Schema::table('payment_correction_requests', function (Blueprint $table) {
            $table->string('reason_category', 60)->nullable();
            $table->string('evidence_reference', 500)->nullable();
            $table->string('evidence_path')->nullable();
            $table->string('evidence_name')->nullable();
            $table->timestamp('withdrawn_at')->nullable();
        });
        Schema::create('workflow_notices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('payment_correction_request_id')->constrained()->cascadeOnDelete();
            $table->string('event', 30);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'payment_correction_request_id', 'event'], 'workflow_notice_event_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_notices');
        Schema::table('payment_correction_requests', fn (Blueprint $table) => $table->dropColumn(['reason_category', 'evidence_reference', 'evidence_path', 'evidence_name', 'withdrawn_at']));
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('chapel_catalog_id');
            $table->dropColumn('chapel_schedule_acknowledgment');
        });
        Schema::table('add_on_catalogs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn(['chapel_address', 'chapel_identity']);
        });
    }
};
