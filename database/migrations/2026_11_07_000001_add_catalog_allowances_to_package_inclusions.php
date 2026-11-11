<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('package_inclusions', function (Blueprint $table) {
            $table->foreignId('add_on_catalog_id')->nullable()->constrained()->restrictOnDelete();
            $table->unsignedInteger('included_quantity')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('package_inclusions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('add_on_catalog_id');
            $table->dropColumn('included_quantity');
        });
    }
};
