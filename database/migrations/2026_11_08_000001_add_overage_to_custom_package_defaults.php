<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('package_inclusions', function (Blueprint $table) {
            $table->decimal('included_kilometers', 12, 2)->nullable()->change();
        });
        Schema::table('custom_package_defaults', function (Blueprint $table) {
            foreach (['hearse_kilometers', 'embalming_extra_rate', 'home_viewing_extra_rate', 'retrieval_extra_rate', 'hearse_extra_rate'] as $column) {
                $table->decimal($column, 12, 2)->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('custom_package_defaults', function (Blueprint $table) {
            $table->dropColumn(['hearse_kilometers', 'embalming_extra_rate', 'home_viewing_extra_rate', 'retrieval_extra_rate', 'hearse_extra_rate']);
        });
        Schema::table('package_inclusions', function (Blueprint $table) {
            $table->unsignedInteger('included_kilometers')->nullable()->change();
        });
    }
};
