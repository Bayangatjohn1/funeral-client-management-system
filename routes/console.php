<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('names:backfill-generated', function () {
    $counts = ['clients' => 0, 'deceased' => 0, 'users' => 0];

    if (Schema::hasTable('clients')) {
        DB::table('clients')
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    $parts = \App\Models\Client::normalizedNameParts((array) $row);
                    $fullName = \App\Models\Client::buildFullName(
                        $parts['first_name'],
                        $parts['middle_name'],
                        $parts['last_name'],
                        $parts['suffix'],
                    );

                    DB::table('clients')->where('id', $row->id)->update([
                        'first_name' => $parts['first_name'],
                        'middle_name' => $parts['middle_name'],
                        'last_name' => $parts['last_name'],
                        'suffix' => $parts['suffix'],
                        'full_name' => $fullName,
                    ]);
                    $counts['clients']++;
                }
            });
    }

    if (Schema::hasTable('deceased')) {
        DB::table('deceased')
            ->select(['id', 'first_name', 'middle_name', 'last_name', 'suffix'])
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    $parts = \App\Models\Deceased::normalizedNameParts((array) $row);
                    $fullName = \App\Models\Deceased::buildFullName(
                        $parts['first_name'],
                        $parts['middle_name'],
                        $parts['last_name'],
                        $parts['suffix'],
                    );

                    DB::table('deceased')->where('id', $row->id)->update([
                        'first_name' => $parts['first_name'],
                        'middle_name' => $parts['middle_name'],
                        'last_name' => $parts['last_name'],
                        'suffix' => $parts['suffix'],
                        'full_name' => $fullName,
                    ]);
                    $counts['deceased']++;
                }
            });
    }

    if (Schema::hasTable('users') && Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
        DB::table('users')
            ->select(['id', 'first_name', 'last_name'])
            ->whereNotNull('first_name')
            ->whereNotNull('last_name')
            ->orderBy('id')
            ->chunkById(200, function ($rows) use (&$counts) {
                foreach ($rows as $row) {
                    $firstName = \App\Models\User::cleanNamePart($row->first_name);
                    $lastName = \App\Models\User::cleanNamePart($row->last_name);

                    if (! $firstName || ! $lastName) {
                        continue;
                    }

                    DB::table('users')->where('id', $row->id)->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'name' => \App\Models\User::buildFullName($firstName, null, $lastName),
                    ]);
                    $counts['users']++;
                }
            });
    }

    $this->info("Backfilled {$counts['clients']} clients, {$counts['deceased']} deceased records, and {$counts['users']} users.");
})->purpose('Recompute generated display names from normalized name columns');

Artisan::command('packages:backfill-items', function () {
    if (! Schema::hasTable('packages') || ! Schema::hasTable('package_inclusions') || ! Schema::hasTable('package_freebies')) {
        $this->warn('Package tables are not available.');
        return 1;
    }

    $counts = ['inclusions' => 0, 'freebies' => 0];

    DB::table('packages')
        ->select(['id', 'inclusions', 'freebies'])
        ->orderBy('id')
        ->chunkById(100, function ($packages) use (&$counts) {
            foreach ($packages as $package) {
                if (! DB::table('package_inclusions')->where('package_id', $package->id)->exists()) {
                    foreach (package_backfill_items($package->inclusions ?? '') as $index => $item) {
                        DB::table('package_inclusions')->insert([
                            'package_id' => $package->id,
                            'inclusion_name' => $item,
                            'sort_order' => $index,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $counts['inclusions']++;
                    }
                }

                if (! DB::table('package_freebies')->where('package_id', $package->id)->exists()) {
                    foreach (package_backfill_items($package->freebies ?? '') as $index => $item) {
                        DB::table('package_freebies')->insert([
                            'package_id' => $package->id,
                            'freebie_name' => $item,
                            'sort_order' => $index,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                        $counts['freebies']++;
                    }
                }
            }
        });

    $this->info("Backfilled {$counts['inclusions']} inclusions and {$counts['freebies']} freebies.");
    return 0;
})->purpose('Backfill package inclusion and freebie rows from legacy package text columns');

if (! function_exists('package_backfill_items')) {
    function package_backfill_items(?string $raw): array
    {
        $raw = trim((string) $raw);

        if ($raw === '') {
            return [];
        }

        if (str_contains($raw, "\n") || str_contains($raw, "\r")) {
            return array_values(array_filter(array_map('trim', preg_split('/\R/u', $raw))));
        }

        return [$raw];
    }
}
