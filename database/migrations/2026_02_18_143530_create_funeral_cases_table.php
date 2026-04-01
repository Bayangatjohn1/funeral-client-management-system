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
    Schema::create('funeral_cases', function (Blueprint $table) {
        $table->id();
        $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
        $table->foreignId('client_id')->constrained('clients')->cascadeOnDelete();
        $table->foreignId('deceased_id')->constrained('deceased')->cascadeOnDelete();
        $table->string('case_code')->unique();
        $table->string('service_package')->nullable();
        $table->string('coffin_type')->nullable();
        $table->decimal('total_amount', 12, 2)->default(0);
        $table->enum('payment_status', ['UNPAID','PAID'])->default('UNPAID');
        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('funeral_cases');
    }
};
