<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills first_name, middle_name, last_name, suffix, and relationship
 * on the clients table by parsing the existing full_name and
 * relationship_to_deceased values.
 *
 * Name-split heuristic (best-effort; data entry should use split fields going forward):
 *   1 token  → first_name only
 *   2 tokens → first_name + last_name
 *   3 tokens → first_name + middle_name + last_name
 *   4+ tokens→ first_name + middle tokens joined → last_name
 *
 * Suffix detection: if the last word is a known suffix token it is extracted.
 */
return new class extends Migration
{
    private const KNOWN_SUFFIXES = ['jr', 'sr', 'ii', 'iii', 'iv', 'v', 'vi'];

    public function up(): void
    {
        if (! Schema::hasColumn('clients', 'first_name')) {
            return;
        }

        DB::table('clients')
            ->whereNull('first_name')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    [$firstName, $middleName, $lastName, $suffix] = $this->parseName($row->full_name ?? '');

                    $relationship = null;
                    if (Schema::hasColumn('clients', 'relationship_to_deceased')) {
                        $raw = $row->relationship_to_deceased ?? null;
                        $relationship = ($raw && $raw !== 'Other') ? $raw : null;
                    }

                    $update = [
                        'first_name'  => $firstName,
                        'middle_name' => $middleName,
                        'last_name'   => $lastName,
                        'suffix'      => $suffix,
                    ];

                    if (Schema::hasColumn('clients', 'relationship')) {
                        $update['relationship'] = $relationship;
                    }

                    DB::table('clients')->where('id', $row->id)->update($update);
                }
            });
    }

    public function down(): void
    {
        // Not reversible — data was populated from existing full_name.
    }

    private function parseName(string $fullName): array
    {
        $parts = array_values(array_filter(array_map('trim', explode(' ', trim($fullName)))));

        if (empty($parts)) {
            return ['', null, null, null];
        }

        $suffix = null;
        $last = strtolower(end($parts));
        if (in_array($last, self::KNOWN_SUFFIXES, true) && count($parts) > 1) {
            $suffix = array_pop($parts);
        }

        $count = count($parts);

        if ($count === 1) {
            return [$parts[0], null, null, $suffix];
        }

        if ($count === 2) {
            return [$parts[0], null, $parts[1], $suffix];
        }

        // 3+ tokens: first / [middle tokens] / last
        $firstName  = array_shift($parts);
        $lastName   = array_pop($parts);
        $middleName = implode(' ', $parts) ?: null;

        return [$firstName, $middleName, $lastName, $suffix];
    }
};
