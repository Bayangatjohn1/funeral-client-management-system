<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('case_add_ons')) {
            Schema::create('case_add_ons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('funeral_case_id')->constrained('funeral_cases')->cascadeOnDelete();
                $table->foreignId('package_add_on_id')->nullable()->constrained('package_add_ons')->nullOnDelete();
                $table->string('add_on_name_snapshot');
                $table->text('add_on_description_snapshot')->nullable();
                $table->decimal('add_on_price_snapshot', 12, 2);
                $table->unsignedInteger('quantity')->default(1);
                $table->decimal('line_total', 12, 2);
                $table->timestamps();

                $table->index('funeral_case_id');
                $table->index('package_add_on_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_add_ons');
    }
};
