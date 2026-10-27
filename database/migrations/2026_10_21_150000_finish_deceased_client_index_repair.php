<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }
        // The older repair attempted DROP UNIQUE before installing an alternative FK backing index.
        // MariaDB retained the unique index for the single-column client FK and the error was swallowed.
        if (!Schema::hasIndex('deceased', 'deceased_client_id_branch_id_index')) {
            Schema::table('deceased', fn (Blueprint $table) => $table->index(['client_id', 'branch_id'], 'deceased_client_id_branch_id_index'));
        }
        if (Schema::hasIndex('deceased', 'deceased_client_id_branch_id_unique')) {
            Schema::table('deceased', fn (Blueprint $table) => $table->dropUnique('deceased_client_id_branch_id_unique'));
        }
    }

    public function down(): void
    {
        // Forward repair of the April migration's intended one-to-many relationship.
        // Reintroducing uniqueness could reject legitimate preserved records; do not recreate it on rollback.
    }
};
