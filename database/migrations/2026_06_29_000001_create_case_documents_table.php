<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('case_documents')) {
            Schema::create('case_documents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('case_id')->constrained('funeral_cases')->cascadeOnDelete();
                $table->string('document_type', 60);
                $table->string('contract_number', 40)->nullable();
                $table->string('file_name');
                $table->string('file_path');
                $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('generated_at')->nullable();

                $table->index('case_id');
                $table->index('generated_by');
                $table->unique('contract_number');
                $table->unique(['case_id', 'document_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_documents');
    }
};
