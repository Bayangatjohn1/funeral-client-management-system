<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\IntakeDraft;
use App\Support\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class IntakeDraftController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $branchIds = $user->branchScopeIds();

        $drafts = IntakeDraft::with(['branch:id,branch_code,branch_name'])
            ->where('created_by', $user->id)
            ->whereIn('branch_id', $branchIds)
            ->where('status', IntakeDraft::STATUS_IN_PROGRESS)
            ->latest('last_saved_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('staff.intake.drafts.index', compact('drafts'));
    }

    public function save(Request $request)
    {
        $user = $request->user();
        $validated = $request->validate([
            'intake_draft_id' => ['nullable', 'integer', 'exists:intake_drafts,id'],
            'entry_mode' => ['nullable', Rule::in(['main', 'other'])],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'current_step' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $entryMode = $validated['entry_mode'] ?? 'main';
        $branchId = $this->resolveDraftBranchId($request, $entryMode);
        $returnTo = $this->safeReturnTo($request->input('return_to'));
        $draft = null;

        DB::transaction(function () use ($request, $user, $validated, $entryMode, $branchId, &$draft) {
            if (! empty($validated['intake_draft_id'])) {
                $draft = IntakeDraft::whereKey($validated['intake_draft_id'])
                    ->where('created_by', $user->id)
                    ->where('status', IntakeDraft::STATUS_IN_PROGRESS)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! in_array((int) $draft->branch_id, $user->branchScopeIds(), true)) {
                    abort(403);
                }
            } else {
                $draft = new IntakeDraft([
                    'created_by' => $user->id,
                    'status' => IntakeDraft::STATUS_IN_PROGRESS,
                ]);
            }

            $draft->fill([
                'branch_id' => $branchId,
                'entry_mode' => $entryMode,
                'payload' => $this->draftPayload($request),
                'current_step' => (int) ($validated['current_step'] ?? 1),
                'last_saved_at' => now(),
            ]);
            $draft->save();

            AuditLogger::log(
                'intake_draft.saved',
                'update',
                'intake_draft',
                $draft->id,
                ['draft_number' => $draft->draft_number, 'entry_mode' => $entryMode],
                $branchId,
                $branchId,
                'success',
                'Intake draft saved',
                'Intake draft saved'
            );
        });

        $editRouteParameters = ['draft' => $draft];
        if ($returnTo) {
            $editRouteParameters['return_to'] = $returnTo;
        }

        return redirect()
            ->route('intake.drafts.edit', $editRouteParameters)
            ->with('success', 'Draft saved. You can resume it anytime from Intake Drafts.');
    }

    public function edit(Request $request, IntakeDraft $draft)
    {
        $this->authorizeDraft($draft);
        $returnTo = $this->safeReturnTo($request->query('return_to'));

        if ($draft->entry_mode === 'other') {
            return app(IntakeController::class)->renderFormForDraft('other', $draft, $returnTo);
        }

        return app(IntakeController::class)->renderFormForDraft('main', $draft, $returnTo);
    }

    public function destroy(IntakeDraft $draft)
    {
        $this->authorizeDraft($draft);

        $draft->update([
            'status' => IntakeDraft::STATUS_DISCARDED,
        ]);
        $draft->delete();

        AuditLogger::log(
            'intake_draft.discarded',
            'delete',
            'intake_draft',
            $draft->id,
            ['draft_number' => $draft->draft_number],
            (int) $draft->branch_id,
            (int) $draft->branch_id,
            'success',
            'Intake draft discarded',
            'Intake draft discarded'
        );

        return redirect()
            ->route('funeral-cases.index', ['tab' => 'draft', 'record_scope' => 'main'])
            ->with('success', 'Draft discarded.');
    }

    private function resolveDraftBranchId(Request $request, string $entryMode): int
    {
        $user = $request->user();
        $operationalBranchId = (int) ($user->operationalBranchId() ?? $user->branch_id ?? 0);

        if ($entryMode === 'other') {
            if (! $user->isMainBranchAdmin()) {
                abort(403, 'Only Main Branch Admin can save other-branch drafts.');
            }

            $branchId = (int) $request->input('branch_id');
            $branch = Branch::whereKey($branchId)->where('is_active', true)->first();
            if (! $branch || strtoupper((string) $branch->branch_code) === 'BR001') {
                return Branch::where('is_active', true)
                    ->whereRaw('UPPER(branch_code) <> ?', ['BR001'])
                    ->orderBy('branch_code')
                    ->value('id') ?? abort(422, 'No active other branch is available.');
            }

            return $branchId;
        }

        if ($operationalBranchId <= 0) {
            abort(403, 'No branch access configured.');
        }

        return $operationalBranchId;
    }

    private function draftPayload(Request $request): array
    {
        $payload = Arr::except($request->except(array_keys($request->allFiles())), [
            '_token',
            '_method',
            'intake_draft_id',
            'current_step',
            'return_to',
        ]);

        return [
            'fields' => $payload,
            'selectedAddOns' => array_values(array_map('strval', Arr::wrap($request->input('selected_add_ons', [])))),
        ];
    }

    private function authorizeDraft(IntakeDraft $draft): void
    {
        $user = request()->user();
        if (! $user || (int) $draft->created_by !== (int) $user->id) {
            abort(403);
        }

        if (! in_array((int) $draft->branch_id, $user->branchScopeIds(), true)) {
            abort(403);
        }

        if ($draft->status !== IntakeDraft::STATUS_IN_PROGRESS) {
            abort(404);
        }
    }

    private function safeReturnTo(mixed $url): ?string
    {
        if (! is_string($url) || trim($url) === '') {
            return null;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        if ($host && ! in_array($host, [request()->getHost(), parse_url(config('app.url'), PHP_URL_HOST)], true)) {
            return null;
        }

        $path = $parts['path'] ?? '';
        if ($path === '' || ! str_starts_with($path, '/')) {
            return null;
        }

        return url($path . (isset($parts['query']) ? '?' . $parts['query'] : ''));
    }
}
