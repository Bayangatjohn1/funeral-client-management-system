<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('case_attachments')) {
            Schema::create('case_attachments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('case_id')->constrained('funeral_cases')->cascadeOnDelete();
                $table->string('attachment_type', 50);
                $table->string('file_name');
                $table->string('file_path');
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index('case_id');
                $table->index('uploaded_by');
                $table->unique(['case_id', 'attachment_type']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('case_attachments');
    }
};
