<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->timestamp('promo_manually_stopped_at')->nullable()->after('promo_is_active');
            $table->foreignId('promo_manually_stopped_by')->nullable()->after('promo_manually_stopped_at')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promo_manually_stopped_by');
            $table->dropColumn('promo_manually_stopped_at');
        });
    }
};
