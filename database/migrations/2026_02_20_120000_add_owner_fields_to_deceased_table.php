<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            $table->string('address')->nullable()->after('client_id');
            $table->date('born')->nullable()->after('full_name');
            $table->date('died')->nullable()->after('born');
            $table->date('interment')->nullable()->after('age');
            $table->string('place_of_cemetery')->nullable()->after('interment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deceased', function (Blueprint $table) {
            $table->dropColumn([
                'address',
                'born',
                'died',
                'interment',
                'place_of_cemetery',
            ]);
        });
    }
};
