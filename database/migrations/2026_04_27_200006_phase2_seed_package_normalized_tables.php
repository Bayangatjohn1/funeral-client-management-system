<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Seeds package_inclusions and package_freebies from the existing TEXT columns
 * on the packages table (packages.inclusions and packages.freebies).
 *
 * Parsing strategy (flexible, handles all common formats):
 *   1. Try JSON decode (handles ["Item A", "Item B"] format).
 *   2. Split on newlines.
 *   3. Split on semicolons.
 *   4. Split on commas (last resort).
 * Each item is trimmed; empty strings are discarded.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('package_inclusions') || ! Schema::hasTable('package_freebies')) {
            return;
        }

        DB::table('packages')->orderBy('id')->each(function ($pkg) {
            $this->seedItems('package_inclusions', 'inclusion_name', $pkg->id, $pkg->inclusions ?? '');
            $this->seedItems('package_freebies',   'freebie_name',   $pkg->id, $pkg->freebies   ?? '');
        });
    }

    public function down(): void
    {
        DB::table('package_inclusions')->delete();
        DB::table('package_freebies')->delete();
    }

    private function seedItems(string $table, string $nameColumn, int $packageId, string $rawText): void
    {
        if (trim($rawText) === '') {
            return;
        }

        // Skip if already seeded for this package.
        if (DB::table($table)->where('package_id', $packageId)->exists()) {
            return;
        }

        $items = $this->parseItems($rawText);
        $now   = now();

        foreach ($items as $order => $name) {
            DB::table($table)->insert([
                'package_id'  => $packageId,
                $nameColumn   => $name,
                'sort_order'  => $order,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        }
    }

    private function parseItems(string $raw): array
    {
        $raw = trim($raw);

        // Try JSON array first.
        if (str_starts_with($raw, '[')) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return array_values(array_filter(array_map('trim', $decoded)));
            }
        }

        // Newlines take priority over commas (avoids splitting "Coffin, Wood, Oak").
        foreach (["\n", ';'] as $delimiter) {
            if (str_contains($raw, $delimiter)) {
                return array_values(array_filter(array_map('trim', explode($delimiter, $raw))));
            }
        }

        // Comma fallback.
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
};
