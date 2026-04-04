<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Safe re-run: drop orphaned table if a previous failed migration left it behind.
        Schema::dropIfExists('temporary_cross_branch_permissions');

        Schema::create('temporary_cross_branch_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('allowed_branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_used')->default(false);
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'is_used'], 'tcbp_user_active_used_idx');
            $table->index(['allowed_branch_id', 'is_active'], 'tcbp_branch_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temporary_cross_branch_permissions');
    }
};
