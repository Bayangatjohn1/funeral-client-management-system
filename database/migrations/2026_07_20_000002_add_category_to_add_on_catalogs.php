<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('add_on_catalogs')) {
            return;
        }

        Schema::table('add_on_catalogs', function (Blueprint $table) {
            if (! Schema::hasColumn('add_on_catalogs', 'category')) {
                $table->string('category', 80)->default('General')->after('name');
            }
        });

        DB::table('add_on_catalogs')
            ->whereNull('category')
            ->orWhere('category', '')
            ->update(['category' => 'General']);
    }

    public function down(): void
    {
        if (! Schema::hasTable('add_on_catalogs') || ! Schema::hasColumn('add_on_catalogs', 'category')) {
            return;
        }

        Schema::table('add_on_catalogs', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
