<?php

namespace App\Services\BranchSync;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class OutboxPayload
{
    public static function encode(string $json): string
    {
        if (strlen($json) > config('branch_sync.max_snapshot_bytes')) {
            throw new RuntimeException('Snapshot exceeds the configured capacity.');
        }
        $payload = Crypt::encryptString('BRS2:'.gzencode($json, 6));
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $packet = (int) DB::selectOne('SELECT @@max_allowed_packet AS bytes')->bytes;
            if (strlen($payload) + 4096 >= $packet) {
                throw new RuntimeException('Database packet capacity is too small for this snapshot. Preserve local records and have an administrator review max_allowed_packet; do not reset synchronization.');
            }
        }
        return $payload;
    }

    public static function decode(string $payload): array
    {
        $json = Crypt::decryptString($payload);
        if (str_starts_with($json, 'BRS2:')) {
            $json = @gzdecode(substr($json, 5), config('branch_sync.max_snapshot_bytes') + 1);
            if ($json === false) {
                throw new RuntimeException('Invalid outbox compression or expansion limit.');
            }
        }
        if (strlen($json) > config('branch_sync.max_snapshot_bytes')) {
            throw new RuntimeException('Outbox exceeds the configured capacity.');
        }
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
