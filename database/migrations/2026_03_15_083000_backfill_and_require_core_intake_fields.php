<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('clients')
            ->select(['id', 'relationship_to_deceased', 'valid_id_type', 'valid_id_number'])
            ->orderBy('id')
            ->chunkById(200, function ($clients) {
                foreach ($clients as $client) {
                    $updates = [];

                    if ($this->isBlank($client->relationship_to_deceased ?? null)) {
                        $updates['relationship_to_deceased'] = 'Other';
                    }
                    if ($this->isBlank($client->valid_id_type ?? null)) {
                        $updates['valid_id_type'] = 'Legacy Record';
                    }
                    if ($this->isBlank($client->valid_id_number ?? null)) {
                        $updates['valid_id_number'] = sprintf('LEGACY-%06d', $client->id);
                    }

                    if ($updates !== []) {
                        DB::table('clients')->where('id', $client->id)->update($updates);
                    }
                }
            });

        DB::table('funeral_cases')
            ->select(['id', 'service_requested_at', 'wake_location', 'funeral_service_at', 'reported_at', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($cases) {
                foreach ($cases as $case) {
                    $updates = [];
                    $fallbackDate = $this->resolveFallbackDate($case->service_requested_at, $case->reported_at, $case->created_at);

                    if ($this->isBlank($case->service_requested_at ?? null)) {
                        $updates['service_requested_at'] = $fallbackDate;
                    }
                    if ($this->isBlank($case->wake_location ?? null)) {
                        $updates['wake_location'] = 'Not specified';
                    }
                    if ($this->isBlank($case->funeral_service_at ?? null)) {
                        $updates['funeral_service_at'] = $fallbackDate;
                    }

                    if ($updates !== []) {
                        DB::table('funeral_cases')->where('id', $case->id)->update($updates);
                    }
                }
            });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('relationship_to_deceased')->nullable(false)->change();
            $table->string('valid_id_type', 100)->nullable(false)->change();
            $table->string('valid_id_number', 100)->nullable(false)->change();
        });

        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->date('service_requested_at')->nullable(false)->change();
            $table->string('wake_location')->nullable(false)->change();
            $table->date('funeral_service_at')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('funeral_cases', function (Blueprint $table) {
            $table->date('service_requested_at')->nullable()->change();
            $table->string('wake_location')->nullable()->change();
            $table->date('funeral_service_at')->nullable()->change();
        });

        Schema::table('clients', function (Blueprint $table) {
            $table->string('relationship_to_deceased')->nullable()->change();
            $table->string('valid_id_type', 100)->nullable()->change();
            $table->string('valid_id_number', 100)->nullable()->change();
        });
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function resolveFallbackDate(mixed ...$values): string
    {
        foreach ($values as $value) {
            if ($value === null || trim((string) $value) === '') {
                continue;
            }

            try {
                return Carbon::parse((string) $value)->toDateString();
            } catch (\Throwable $e) {
                continue;
            }
        }

        return now()->toDateString();
    }
};
