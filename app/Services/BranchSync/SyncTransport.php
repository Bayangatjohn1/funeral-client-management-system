<?php

namespace App\Services\BranchSync;

use Illuminate\Encryption\Encrypter;
use RuntimeException;

class SyncTransport
{
    public static function seal(array $payload, string $token): string
    {
        $json = json_encode($payload, JSON_THROW_ON_ERROR);
        if (strlen($json) > config('branch_sync.max_snapshot_bytes') + 1024) {
            throw new RuntimeException('Sync snapshot exceeds the configured limit.');
        }
        return (new Encrypter(hash('sha256', $token, true), 'AES-256-CBC'))
            ->encryptString('BRS2:'.gzencode($json, 6));
    }

    public static function open(string $sealed, string $token): array
    {
        if (strlen($sealed) > config('branch_sync.max_snapshot_bytes') * 2) {
            throw new RuntimeException('Sync snapshot exceeds the configured limit.');
        }
        $json = (new Encrypter(hash('sha256', $token, true), 'AES-256-CBC'))->decryptString($sealed);
        if (str_starts_with($json, 'BRS2:')) {
            // Bound decompression as well as ciphertext size; never accept a compressed expansion bomb.
            $json = @gzdecode(substr($json, 5), config('branch_sync.max_snapshot_bytes') + 1025);
            if ($json === false) {
                throw new RuntimeException('Invalid compressed snapshot or expansion limit exceeded.');
            }
        }
        if (strlen($json) > config('branch_sync.max_snapshot_bytes') + 1024) {
            throw new RuntimeException('Sync snapshot exceeds the configured limit.');
        }
        return json_decode($json, true, 512, JSON_THROW_ON_ERROR);
    }
}
