<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('payments')->where('status', 'VALID')->update(['status' => 'POSTED']);
        DB::table('payments')->where('status', 'VOID')->update(['status' => 'VOIDED']);
        Schema::table('payments', fn (Blueprint $table) => $table->string('status', 20)->default('POSTED')->change());
    }

    public function down(): void
    {
        DB::table('payments')->where('status', 'POSTED')->update(['status' => 'VALID']);
        DB::table('payments')->where('status', 'VOIDED')->update(['status' => 'VOID']);
        Schema::table('payments', fn (Blueprint $table) => $table->string('status', 20)->default('VALID')->change());
    }
};
