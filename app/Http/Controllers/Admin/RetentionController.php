<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FuneralCase;
use App\Support\AuditLogger;
use Illuminate\Http\Request;

class RetentionController extends Controller
{
    public function index(Request $request)
    {
        $cases = FuneralCase::withoutGlobalScope('branch_scope')->with(['branch', 'client', 'deceased', 'legalHoldPlacedBy'])
            ->where('case_status', 'COMPLETED')
            ->whereNotNull('retention_end_date')
            ->when($request->status, fn ($q, $status) => $q->where('retention_status', $status))
            ->orderBy('retention_end_date')->paginate(25)->withQueryString();
        $counts = [
            'due_90' => FuneralCase::withoutGlobalScope('branch_scope')->whereBetween('retention_end_date', [today(), today()->addDays(90)])->count(),
            'due_30' => FuneralCase::withoutGlobalScope('branch_scope')->whereBetween('retention_end_date', [today(), today()->addDays(30)])->count(),
            'pending' => FuneralCase::withoutGlobalScope('branch_scope')->where('retention_status', 'pending_disposal_review')->count(),
            'legal_hold' => FuneralCase::withoutGlobalScope('branch_scope')->whereNotNull('legal_hold_at')->count(),
        ];
        return view('admin.retention.index', compact('cases', 'counts'));
    }

    public function placeHold(Request $request, FuneralCase $funeralCase)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        $funeralCase->update(['legal_hold_at' => now(), 'legal_hold_by' => $request->user()->id, 'legal_hold_reason' => $validated['reason'], 'legal_hold_released_at' => null, 'legal_hold_released_by' => null, 'retention_status' => 'legal_hold']);
        AuditLogger::log('retention.legal_hold_placed', 'UPDATE', 'funeral_case', $funeralCase->id, ['reason' => $validated['reason']], $funeralCase->branch_id);
        return back()->with('success', 'Legal hold placed. This record cannot be disposed.');
    }

    public function releaseHold(Request $request, FuneralCase $funeralCase)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:10', 'max:2000']]);
        abort_unless($funeralCase->legal_hold_at, 422, 'This case has no active legal hold.');
        $status = $funeralCase->retention_end_date?->isPast() ? 'pending_disposal_review' : 'retained';
        $funeralCase->update(['legal_hold_at' => null, 'legal_hold_released_at' => now(), 'legal_hold_released_by' => $request->user()->id, 'retention_status' => $status]);
        AuditLogger::log('retention.legal_hold_released', 'UPDATE', 'funeral_case', $funeralCase->id, ['reason' => $validated['reason']], $funeralCase->branch_id);
        return back()->with('success', 'Legal hold released.');
    }
}
