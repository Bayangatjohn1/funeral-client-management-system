<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('branch_sync_peers', function (Blueprint $table) {
            // Existing installations retain their ownership; newly issued peers explicitly start prepared.
            $table->string('activation_state', 16)->default('active');
            $table->string('bootstrap_hash', 64)->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('activated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('branch_sync_peers', fn (Blueprint $table) => $table->dropColumn([
            'activation_state', 'bootstrap_hash', 'ready_at', 'activated_at',
        ]));
    }
};
