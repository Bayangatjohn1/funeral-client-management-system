<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('case_document_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_document_id')->constrained('case_documents')->cascadeOnDelete();
            $table->unsignedInteger('revision_number');
            $table->json('contract_snapshot');
            $table->string('file_name');
            $table->string('file_path');
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable();
            $table->foreignId('superseded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('superseded_at');
            $table->string('reason', 500);
            $table->unique(['case_document_id', 'revision_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('case_document_revisions');
    }
};
