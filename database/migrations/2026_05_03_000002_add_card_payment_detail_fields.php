<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'card_type')) {
                $table->string('card_type', 20)->nullable()->after('approval_code');
            }
            if (! Schema::hasColumn('payments', 'terminal_provider')) {
                $table->string('terminal_provider', 80)->nullable()->after('card_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $columns = [];
            foreach (['card_type', 'terminal_provider'] as $column) {
                if (Schema::hasColumn('payments', $column)) {
                    $columns[] = $column;
                }
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
