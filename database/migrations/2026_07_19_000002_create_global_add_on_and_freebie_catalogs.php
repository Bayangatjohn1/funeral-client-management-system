<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('add_on_catalogs')) {
            Schema::create('add_on_catalogs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->decimal('price', 12, 2)->default(0);
                $table->string('unit', 40)->default('item');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        if (! Schema::hasTable('add_on_catalog_package_add_on')) {
            Schema::create('add_on_catalog_package_add_on', function (Blueprint $table) {
                $table->id();
                $table->foreignId('add_on_catalog_id')->constrained('add_on_catalogs')->cascadeOnDelete();
                $table->foreignId('package_add_on_id')->constrained('package_add_ons')->cascadeOnDelete();
                $table->timestamps();

                $table->unique('package_add_on_id');
            });
        }

        if (! Schema::hasTable('freebie_catalogs')) {
            Schema::create('freebie_catalogs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('default_unit', 40)->default('item');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
            });
        }

        $this->backfillAddOnCatalog();
        $this->backfillFreebieCatalog();

        if (
            Schema::hasTable('package_freebies')
            && Schema::hasTable('freebie_catalogs')
            && Schema::hasColumn('package_freebies', 'freebie_catalog_id')
        ) {
            DB::table('package_freebies')
                ->orderBy('id')
                ->whereNull('freebie_catalog_id')
                ->each(function ($freebie) {
                    $catalogId = DB::table('freebie_catalogs')
                        ->whereRaw('LOWER(name) = ?', [Str::lower(trim((string) $freebie->freebie_name))])
                        ->value('id');

                    if ($catalogId) {
                        DB::table('package_freebies')->where('id', $freebie->id)->update([
                            'freebie_catalog_id' => $catalogId,
                            'is_custom' => false,
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('add_on_catalog_package_add_on');
        Schema::dropIfExists('add_on_catalogs');
        Schema::dropIfExists('freebie_catalogs');
    }

    private function backfillAddOnCatalog(): void
    {
        if (! Schema::hasTable('package_add_ons')) {
            return;
        }

        DB::table('package_add_ons')->orderBy('id')->each(function ($legacy) {
            $name = trim((string) $legacy->name);
            if ($name === '') {
                return;
            }

            $description = trim((string) ($legacy->description ?? ''));
            $price = round((float) $legacy->price, 2);
            $unit = 'item';

            $catalogId = DB::table('add_on_catalogs')
                ->whereRaw('LOWER(name) = ?', [Str::lower($name)])
                ->whereRaw('COALESCE(description, "") = ?', [$description])
                ->where('price', $price)
                ->where('unit', $unit)
                ->value('id');

            if (! $catalogId) {
                $catalogId = DB::table('add_on_catalogs')->insertGetId([
                    'name' => $name,
                    'description' => $description !== '' ? $description : null,
                    'price' => $price,
                    'unit' => $unit,
                    'is_active' => (bool) $legacy->is_active,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('add_on_catalog_package_add_on')->updateOrInsert(
                ['package_add_on_id' => $legacy->id],
                [
                    'add_on_catalog_id' => $catalogId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }

    private function backfillFreebieCatalog(): void
    {
        if (! Schema::hasTable('package_freebies')) {
            return;
        }

        DB::table('package_freebies')->orderBy('id')->each(function ($freebie) {
            $name = trim((string) $freebie->freebie_name);
            if ($name === '') {
                return;
            }

            DB::table('freebie_catalogs')->updateOrInsert(
                ['name' => $name],
                [
                    'default_unit' => $freebie->unit ?: 'item',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        });
    }
};
