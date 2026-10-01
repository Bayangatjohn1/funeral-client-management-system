<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('casket_catalogs', function (Blueprint $table) {
            $table->boolean('is_available')->default(true)->after('is_active')->index();
        });
    }

    public function down(): void
    {
        Schema::table('casket_catalogs', function (Blueprint $table) {
            $table->dropColumn('is_available');
        });
    }
};
