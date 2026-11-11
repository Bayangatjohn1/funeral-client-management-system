<?php
// Read-only aggregate audit; never completes or changes a case.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$active = App\Models\FuneralCase::withoutGlobalScope('branch_scope')->where('case_status', 'ACTIVE');
$due = (clone $active)->whereNotNull('interment_at')->where('interment_at', '<=', now());
echo json_encode([
    'time' => now()->toIso8601String(),
    'timezone' => config('app.timezone'),
    'sync_mode' => config('branch_sync.mode'),
    'active' => (clone $active)->count(),
    'past_due_active' => (clone $due)->count(),
    'past_due_locally_owned' => (clone $due)->locallyOwned()->count(),
    'active_missing_case_interment' => (clone $active)->whereNull('interment_at')->count(),
    'active_missing_case_with_past_deceased_interment' => (clone $active)->whereNull('interment_at')->whereHas('deceased', fn ($q) => $q->where('interment_at', '<=', now()))->count(),
], JSON_PRETTY_PRINT), PHP_EOL;
