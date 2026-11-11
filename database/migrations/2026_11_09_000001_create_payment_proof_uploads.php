<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_proof_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained();
            $table->string('session_hash', 64);
            $table->uuid('form_id');
            $table->string('context', 20);
            $table->string('token_hash', 64);
            $table->dateTime('expires_at');
            $table->dateTime('retain_until')->index();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('claimed_at')->nullable()->index();
            $table->string('disk', 40);
            $table->string('path')->nullable();
            $table->string('mime', 80)->nullable();
            $table->string('original_name')->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->timestamps();
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignUuid('proof_upload_id')->nullable()->constrained('payment_proof_uploads');
            $table->string('proof_path')->nullable();
            $table->string('proof_mime', 80)->nullable();
            $table->string('proof_name')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('proof_upload_id');
            $table->dropColumn(['proof_path', 'proof_mime', 'proof_name']);
        });
        Schema::dropIfExists('payment_proof_uploads');
    }
};
