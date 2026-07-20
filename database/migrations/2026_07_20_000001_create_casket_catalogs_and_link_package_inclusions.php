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
        if (! Schema::hasTable('casket_catalogs')) {
            Schema::create('casket_catalogs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type_or_material')->nullable();
                $table->decimal('standard_price', 12, 2)->default(0);
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('is_active');
                $table->unique(['name', 'type_or_material'], 'casket_catalog_name_material_unique');
            });
        }

        Schema::table('package_inclusions', function (Blueprint $table) {
            if (! Schema::hasColumn('package_inclusions', 'casket_catalog_id')) {
                $table->foreignId('casket_catalog_id')
                    ->nullable()
                    ->after('service_type')
                    ->constrained('casket_catalogs')
                    ->nullOnDelete();
            }
        });

        $this->backfillFromPackageInclusions();
        $this->backfillFromLegacyPackages();
    }

    public function down(): void
    {
        Schema::table('package_inclusions', function (Blueprint $table) {
            if (Schema::hasColumn('package_inclusions', 'casket_catalog_id')) {
                $table->dropConstrainedForeignId('casket_catalog_id');
            }
        });

        Schema::dropIfExists('casket_catalogs');
    }

    private function backfillFromPackageInclusions(): void
    {
        if (! Schema::hasTable('package_inclusions')) {
            return;
        }

        DB::table('package_inclusions')
            ->where('service_type', 'casket')
            ->whereNotNull('casket_type')
            ->orderBy('id')
            ->each(function ($inclusion) {
                $name = $this->cleanName($inclusion->casket_type);
                if ($name === '') {
                    return;
                }

                $catalogId = $this->catalogIdFor($name);
                DB::table('package_inclusions')->where('id', $inclusion->id)->update([
                    'casket_catalog_id' => $catalogId,
                    'updated_at' => now(),
                ]);
            });
    }

    private function backfillFromLegacyPackages(): void
    {
        if (! Schema::hasTable('packages') || ! Schema::hasTable('package_inclusions')) {
            return;
        }

        DB::table('packages')
            ->whereNotNull('coffin_type')
            ->orderBy('id')
            ->each(function ($package) {
                $name = $this->cleanName($package->coffin_type);
                if ($name === '') {
                    return;
                }

                $catalogId = $this->catalogIdFor($name);

                $existing = DB::table('package_inclusions')
                    ->where('package_id', $package->id)
                    ->where('service_type', 'casket')
                    ->first();

                if ($existing) {
                    if (empty($existing->casket_catalog_id)) {
                        DB::table('package_inclusions')->where('id', $existing->id)->update([
                            'casket_catalog_id' => $catalogId,
                            'casket_type' => $existing->casket_type ?: $name,
                            'updated_at' => now(),
                        ]);
                    }
                    return;
                }

                DB::table('package_inclusions')->insert([
                    'package_id' => $package->id,
                    'service_type' => 'casket',
                    'casket_catalog_id' => $catalogId,
                    'is_custom' => false,
                    'inclusion_name' => 'Casket',
                    'casket_type' => $name,
                    'sort_order' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    private function catalogIdFor(string $name): int
    {
        $normalized = Str::lower($name);

        $existing = DB::table('casket_catalogs')
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->where(function ($query) {
                $query->whereNull('type_or_material')->orWhere('type_or_material', '');
            })
            ->value('id');

        if ($existing) {
            return (int) $existing;
        }

        return (int) DB::table('casket_catalogs')->insertGetId([
            'name' => $name,
            'type_or_material' => null,
            'standard_price' => 0,
            'description' => 'Migrated from existing package casket text. Price not configured.',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function cleanName(?string $value): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $value));
    }
};
