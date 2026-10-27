<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->uuid('submission_id')->nullable();
            $table->char('submission_hash', 64)->nullable();
            $table->unique(['branch_id', 'submission_id'], 'payments_branch_submission_unique');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique('payments_branch_submission_unique');
            $table->dropColumn(['submission_id', 'submission_hash']);
        });
    }
};
