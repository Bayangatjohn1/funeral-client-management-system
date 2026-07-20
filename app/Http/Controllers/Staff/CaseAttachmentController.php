<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTarpaulinAttachmentRequest;
use App\Models\CaseAttachment;
use App\Models\FuneralCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CaseAttachmentController extends Controller
{
    public function store(StoreTarpaulinAttachmentRequest $request, FuneralCase $funeral_case): RedirectResponse
    {
        $this->authorize('uploadTarpaulin', $funeral_case);

        if ($funeral_case->tarpaulinAttachment()->exists()) {
            return back()->withErrors([
                'photo' => 'A tarpaulin photo already exists. Use Replace to update it.',
            ]);
        }

        $this->saveTarpaulinAttachment($request, $funeral_case);

        return back()->with('success', 'Tarpaulin photo uploaded successfully.');
    }

    public function update(StoreTarpaulinAttachmentRequest $request, FuneralCase $funeral_case): RedirectResponse
    {
        $this->authorize('replaceTarpaulin', $funeral_case);

        $this->saveTarpaulinAttachment($request, $funeral_case, true);

        return back()->with('success', 'Tarpaulin photo replaced successfully.');
    }

    public function destroy(FuneralCase $funeral_case): RedirectResponse
    {
        $this->authorize('deleteTarpaulin', $funeral_case);

        $attachment = $funeral_case->tarpaulinAttachment()->first();

        if (! $attachment) {
            return back()->withErrors([
                'photo' => 'No tarpaulin photo is attached to this case.',
            ]);
        }

        DB::transaction(function () use ($attachment) {
            Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        });

        return back()->with('success', 'Tarpaulin photo deleted successfully.');
    }

    private function saveTarpaulinAttachment(StoreTarpaulinAttachmentRequest $request, FuneralCase $funeralCase, bool $replace = false): CaseAttachment
    {
        $file = $request->file('photo');
        $newPath = $file->store('uploads/tarpaulin', 'public');
        $oldPath = null;

        try {
            $attachment = DB::transaction(function () use ($file, $funeralCase, $newPath, $replace, &$oldPath) {
                $attachment = $funeralCase->tarpaulinAttachment()->lockForUpdate()->first();
                $oldPath = $attachment?->file_path;

                if (! $attachment) {
                    $attachment = new CaseAttachment([
                        'case_id' => $funeralCase->id,
                        'attachment_type' => CaseAttachment::TYPE_TARPAULIN,
                    ]);
                } elseif (! $replace) {
                    Storage::disk('public')->delete($newPath);
                    abort(409, 'A tarpaulin photo already exists for this case.');
                }

                $attachment->fill([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $newPath,
                    'uploaded_by' => auth()->id(),
                ])->save();

                return $attachment;
            });

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }

            return $attachment;
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }
    }
}
