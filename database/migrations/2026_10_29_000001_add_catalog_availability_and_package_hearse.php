<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up(): void {
  foreach (['hearse_catalogs','freebie_catalogs','add_on_catalogs'] as $table) Schema::table($table, fn (Blueprint $t) => $t->boolean('is_available')->default(true));
  Schema::table('package_inclusions', fn (Blueprint $t) => $t->foreignId('hearse_catalog_id')->nullable()->constrained('hearse_catalogs')->restrictOnDelete());
 }
 public function down(): void {
  Schema::table('package_inclusions', fn (Blueprint $t) => $t->dropConstrainedForeignId('hearse_catalog_id'));
  foreach (['hearse_catalogs','freebie_catalogs','add_on_catalogs'] as $table) Schema::table($table, fn (Blueprint $t) => $t->dropColumn('is_available'));
 }
};
