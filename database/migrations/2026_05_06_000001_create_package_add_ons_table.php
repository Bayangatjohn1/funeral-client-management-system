<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_add_ons')) {
            Schema::create('package_add_ons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('package_id')->constrained('packages')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index(['package_id', 'is_active']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('package_add_ons');
    }
};
