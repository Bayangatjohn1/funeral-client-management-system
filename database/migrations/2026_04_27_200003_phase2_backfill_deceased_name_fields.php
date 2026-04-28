<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfills first_name, middle_name, last_name, suffix on deceased
 * from full_name, and date_of_birth from born, and is_senior from
 * senior_citizen_status.
 */
return new class extends Migration
{
    private const KNOWN_SUFFIXES = ['jr', 'sr', 'ii', 'iii', 'iv', 'v', 'vi'];

    public function up(): void
    {
        if (! Schema::hasColumn('deceased', 'first_name')) {
            return;
        }

        DB::table('deceased')
            ->whereNull('first_name')
            ->orderBy('id')
            ->chunk(200, function ($rows) {
                foreach ($rows as $row) {
                    [$firstName, $middleName, $lastName, $suffix] = $this->parseName($row->full_name ?? '');

                    $update = [
                        'first_name'  => $firstName,
                        'middle_name' => $middleName,
                        'last_name'   => $lastName,
                        'suffix'      => $suffix,
                    ];

                    if (Schema::hasColumn('deceased', 'date_of_birth')) {
                        // born is the existing date-of-birth column
                        $update['date_of_birth'] = $row->born ?? $row->date_of_death ?? null;
                    }

                    if (Schema::hasColumn('deceased', 'is_senior')) {
                        $update['is_senior'] = (bool) ($row->senior_citizen_status ?? false);
                    }

                    DB::table('deceased')->where('id', $row->id)->update($update);
                }
            });
    }

    public function down(): void
    {
        // Not reversible.
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

        $firstName  = array_shift($parts);
        $lastName   = array_pop($parts);
        $middleName = implode(' ', $parts) ?: null;

        return [$firstName, $middleName, $lastName, $suffix];
    }
};
