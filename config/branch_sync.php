<?php

return [
    // standalone preserves existing behavior; central and branch are explicit deployments.
    'mode' => env('BRANCH_SYNC_MODE', 'standalone'),
    'branch_code' => env('BRANCH_SYNC_CODE'),
    'central_url' => env('BRANCH_SYNC_CENTRAL_URL'),
    'token' => env('BRANCH_SYNC_TOKEN'),
    'timeout' => (int) env('BRANCH_SYNC_TIMEOUT', 120),
    'max_snapshot_bytes' => 20 * 1024 * 1024,
    'max_file_bytes' => 10 * 1024 * 1024,
    'max_offline_hours' => (int) env('BRANCH_SYNC_MAX_OFFLINE_HOURS', 168),
];
