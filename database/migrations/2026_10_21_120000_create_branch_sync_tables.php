<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_sync_peers', function (Blueprint $table) {
            $table->id();
            $table->string('branch_code', 10)->unique();
            $table->string('token_hash', 64);
            $table->uuid('installation_id');
            $table->boolean('enabled')->default(true);
            $table->unsignedBigInteger('accepted_version')->default(0);
            $table->string('accepted_hash', 64)->nullable();
            $table->timestamp('last_received_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->boolean('pending_changes')->default(true);
            $table->timestamps();
        });
        Schema::create('branch_sync_records', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 60);
            $table->unsignedBigInteger('local_id');
            $table->uuid('uuid')->unique();
            $table->unique(['entity', 'local_id']);
        });
        Schema::create('branch_sync_state', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->longText('value')->nullable();
            $table->timestamps();
        });
        Schema::create('branch_sync_outbox', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('version')->unique();
            $table->string('fingerprint', 64);
            $table->longText('payload'); // encrypted, including PII; never log contents
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
        });
        Schema::create('branch_sync_file_grants', function (Blueprint $table) {
            $table->id();
            $table->string('branch_code', 10);
            $table->string('hash', 64);
            $table->unique(['branch_code', 'hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_sync_file_grants');
        Schema::dropIfExists('branch_sync_outbox');
        Schema::dropIfExists('branch_sync_state');
        Schema::dropIfExists('branch_sync_records');
        Schema::dropIfExists('branch_sync_peers');
    }
};
