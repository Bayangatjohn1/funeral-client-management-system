<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private array $tables = ['casket_catalogs', 'hearse_catalogs', 'add_on_catalogs', 'freebie_catalogs'];

    public function up(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->string('photo_path')->nullable();
                $table->string('photo_mime', 40)->nullable();
                $table->string('photo_name', 180)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, fn (Blueprint $table) => $table->dropColumn(['photo_path', 'photo_mime', 'photo_name']));
        }
    }
};
