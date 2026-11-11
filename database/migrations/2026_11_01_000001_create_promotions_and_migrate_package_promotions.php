<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('package_id')->nullable()->unique()->constrained('packages')->nullOnDelete();
            $table->string('name', 120)->nullable();
            $table->string('discount_type', 20)->nullable();
            $table->decimal('discount_value', 12, 2)->nullable();
            $table->date('starts_at')->nullable();
            $table->date('ends_at')->nullable();
            $table->boolean('is_enabled')->default(true);
            $table->timestamp('manually_stopped_at')->nullable();
            $table->foreignId('manually_stopped_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['is_enabled', 'starts_at', 'ends_at']);
        });

        // Only packages containing legacy promotion data are migrated. The old columns
        // remain untouched so the data is recoverable until a later approved removal.
        DB::table('packages')->orderBy('id')->chunk(200, function ($packages): void {
            foreach ($packages as $package) {
                $hasData = filled($package->promo_label ?? null)
                    || filled($package->promo_value_type ?? null)
                    || $package->promo_value !== null
                    || $package->promo_starts_at !== null
                    || $package->promo_ends_at !== null
                    // An enabled flag without the required promotion fields is
                    // still an existing (incomplete) configuration. Preserve it
                    // as Needs Review instead of silently treating it as none.
                    || (bool) ($package->promo_is_active ?? false)
                    || $package->promo_manually_stopped_at !== null;
                if (! $hasData) continue;

                DB::table('promotions')->insert([
                    'package_id' => $package->id,
                    'name' => $package->promo_label,
                    'discount_type' => $package->promo_value_type,
                    'discount_value' => $package->promo_value,
                    'starts_at' => $package->promo_starts_at ? substr((string) $package->promo_starts_at, 0, 10) : null,
                    'ends_at' => $package->promo_ends_at ? substr((string) $package->promo_ends_at, 0, 10) : null,
                    'is_enabled' => (bool) $package->promo_is_active,
                    'manually_stopped_at' => $package->promo_manually_stopped_at,
                    'manually_stopped_by' => $package->promo_manually_stopped_by,
                    'created_by' => null,
                    'updated_by' => null,
                    'created_at' => $package->created_at ?? now(),
                    'updated_at' => $package->updated_at ?? now(),
                ]);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
