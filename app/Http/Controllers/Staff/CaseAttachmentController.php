<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTarpaulinAttachmentRequest;
use App\Models\CaseAttachment;
use App\Models\Deceased;
use App\Models\FuneralCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CaseAttachmentController extends Controller
{
    public function saveCaseMedia(Request $request, FuneralCase $funeral_case, string $kind): RedirectResponse
    {
        $this->authorize('manageCaseMedia', $funeral_case);

        $isPhoto = $kind === 'deceased-photo';
        $field = $isPhoto ? 'deceased_photo_file' : 'senior_proof_file';
        $rules = $isPhoto
            ? [$field => 'required|image|mimes:jpg,jpeg,png,webp|max:3072']
            : [$field => 'required|mimes:jpg,jpeg,png,webp,pdf|max:5120'];
        $validated = $request->validate($rules);
        $file = $validated[$field];
        $deceased = $funeral_case->deceased;

        abort_unless($deceased instanceof Deceased, 404);
        if (! $isPhoto && ! $deceased->senior_citizen_status) {
            return back()->withErrors(['senior_proof' => 'Senior proof can only be attached to a case marked as senior citizen.']);
        }

        $type = $isPhoto ? CaseAttachment::TYPE_DECEASED_PHOTO : CaseAttachment::TYPE_SENIOR_PROOF;
        $pathColumn = $isPhoto ? 'photo_path' : 'senior_proof_path';
        $directory = $isPhoto ? 'deceased-photos' : 'senior-proofs';
        $newPath = $file->store($directory, 'public');
        $oldPath = null;

        try {
            DB::transaction(function () use ($funeral_case, $deceased, $file, $newPath, $type, $pathColumn, &$oldPath) {
                $attachment = $funeral_case->attachments()->where('attachment_type', $type)->lockForUpdate()->first();
                $oldPath = $attachment?->file_path ?: $deceased->{$pathColumn};
                $attachment ??= new CaseAttachment([
                    'case_id' => $funeral_case->id,
                    'attachment_type' => $type,
                ]);
                $attachment->fill([
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $newPath,
                    'uploaded_by' => auth()->id(),
                ])->save();
                $deceased->forceFill([$pathColumn => $newPath])->save();
            });

            if ($oldPath && $oldPath !== $newPath) {
                Storage::disk('public')->delete($oldPath);
            }
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($newPath);
            throw $exception;
        }

        return back()->with('success', $isPhoto ? 'Deceased photo saved successfully.' : 'Senior proof saved successfully.');
    }

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
