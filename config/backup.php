<?php

return [
    'disk' => env('BACKUP_DISK', 'local'),
    'directory' => env('BACKUP_DIRECTORY', 'system-backups'),
    'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 14),
    'daily_time' => env('BACKUP_DAILY_TIME', '01:00'),
    'dump_binary' => env('DB_DUMP_BINARY'),
];
