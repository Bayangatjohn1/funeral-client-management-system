<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RestoreRequest;
use App\Models\SystemBackup;
use App\Services\SystemBackupService;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class BackupRecoveryController extends Controller
{
    public function index()
    {
        $backups = SystemBackup::with('creator')->latest()->paginate(20);
        $lastSuccessful = SystemBackup::where('status', 'verified')->latest('verified_at')->first();
        return view('admin.backup-recovery.index', compact('backups', 'lastSuccessful'));
    }

    public function store(Request $request, SystemBackupService $service)
    {
        $validator = Validator::make($request->only('password'), [
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'Enter your account password to continue.',
            'password.current_password' => 'The password you entered is incorrect. Try again.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'createBackup')
                ->with('open_backup_dialog', true);
        }

        try {
            $backup = $service->create('manual', $request->user()->id);
            AuditLogger::log('backup.completed', 'CREATE', 'system_backup', $backup->id, ['type' => 'manual']);
            return back()->with('success', "Backup #{$backup->id} is ready. System records and private files were encrypted and checked successfully.");
        } catch (\Throwable $exception) {
            report($exception);
            $message = str_contains($exception->getMessage(), 'already being created')
                ? 'A backup is already being created. Please wait for it to finish before trying again.'
                : 'The backup could not be created. Nothing in the live system was changed. Please try again or contact the technical administrator.';
            return back()->withErrors(['backup' => $message]);
        }
    }

    public function verify(SystemBackup $systemBackup, SystemBackupService $service)
    {
        try {
            $service->verify($systemBackup);
            AuditLogger::log('backup.verified', 'VERIFY', 'system_backup', $systemBackup->id);
            return back()->with('success', "Backup #{$systemBackup->id} passed the integrity check.");
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['backup' => 'This backup could not be verified. The file may be missing, damaged, or no longer readable.']);
        }
    }

    public function download(SystemBackup $systemBackup)
    {
        abort_unless($systemBackup->path && Storage::disk($systemBackup->disk)->exists($systemBackup->path), 404);
        AuditLogger::log('backup.downloaded', 'VIEW', 'system_backup', $systemBackup->id);
        return Storage::disk($systemBackup->disk)->download($systemBackup->path, basename($systemBackup->path));
    }

    public function requestRestore(Request $request, SystemBackup $systemBackup, SystemBackupService $service)
    {
        $validated = $request->validate([
            'password' => ['required', 'current_password'],
            'reason' => ['required', 'string', 'min:10', 'max:2000'],
            'confirmation' => ['required', 'in:RESTORE FUNERAL SYSTEM'],
        ]);
        $activeRestoreStatuses = ['pending_validation', 'validated_pending_execution', 'executing'];
        if ($systemBackup->restoreRequests()->whereIn('status', $activeRestoreStatuses)->exists()) {
            return back()->withErrors(['restore' => 'This backup already has a restore request waiting for completion.']);
        }

        try {
            $service->verify($systemBackup);
        } catch (\Throwable $exception) {
            report($exception);
            return back()->withErrors(['restore' => 'The restore request could not be prepared because the selected backup did not pass its integrity check.']);
        }

        $restore = DB::transaction(function () use ($systemBackup, $request, $validated) {
            $restore = RestoreRequest::create([
                'system_backup_id' => $systemBackup->id,
                'requested_by' => $request->user()->id,
                'status' => 'validated_pending_execution',
                'reason' => $validated['reason'],
                'validated_at' => now(),
                'validation_results' => ['checksum' => true, 'archive' => true, 'required_files' => true],
            ]);
            $systemBackup->update(['status' => 'restore_pending']);
            return $restore;
        });
        AuditLogger::log('restore.requested', 'CREATE', 'restore_request', $restore->id, ['backup_id' => $systemBackup->id], status: 'pending');
        return back()->with('success', 'Restore request validated and staged. Server execution is still required; production was not overwritten.');
    }
}
