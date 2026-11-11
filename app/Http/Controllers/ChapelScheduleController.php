<?php

namespace App\Http\Controllers;

use App\Models\AddOnCatalog;
use App\Support\ChapelSchedule;
use Illuminate\Http\Request;

class ChapelScheduleController extends Controller
{
    public function __invoke(Request $request, ChapelSchedule $schedule)
    {
        $data = $request->validate([
            'chapel_id' => 'required_without:branch_id|integer',
            'branch_id' => 'required_without:chapel_id|integer|exists:branches,id',
            'start' => 'required|date_format:Y-m-d H:i',
            'end' => 'required|date_format:Y-m-d H:i|after:start',
        ]);
        $chapel = isset($data['chapel_id']) ? AddOnCatalog::findOrFail($data['chapel_id']) : null;
        $branchId = (int) ($chapel ? $chapel->branch_id : $data['branch_id']);
        abort_unless((!$chapel || $chapel->chapel_ready) && (
            $branchId === (int) $request->user()->operationalBranchId() || $request->user()->canEncodeAnyBranch()
        ), 403);

        $result = $chapel ? $schedule->context($chapel, $data['start'], $data['end']) : ['events' => []];
        $result['available_chapel_ids'] = !$chapel || $result['events']
            ? $schedule->availableChapelIds($branchId, $data['start'], $data['end'], $chapel?->id)
            : [];

        return response()->json($result)->header('Cache-Control', 'no-store');
    }
}
