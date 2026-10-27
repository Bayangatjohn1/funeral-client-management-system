<?php

namespace App\Services\CaseDocument;

use App\Models\CaseDocument;
use App\Models\CaseDocumentRevision;
use App\Models\FuneralCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FuneralContractCorrectionService
{
    public function __construct(
        private readonly FuneralContractMapper $mapper,
        private readonly FuneralContractLayout $layout,
    ) {}

    public function birthDate(FuneralCase $case): ?string
    {
        $case->loadMissing('deceased');
        $birth = $case->deceased?->date_of_birth ?: $case->deceased?->born;

        return $birth ? $birth->toDateString() : null;
    }

    public function canCorrectBirthDate(FuneralCase $case, ?CaseDocument $document): bool
    {
        return $document?->isFuneralContract()
            && is_array($document->contract_snapshot)
            && $document->contract_snapshot !== []
            && ! data_get($document->contract_snapshot, 'deceased.date_of_birth')
            && $this->birthDate($case) !== null;
    }

    public function correctBirthDate(FuneralCase $case, int $userId, string $reason): CaseDocument
    {
        $newPath = null;

        try {
            return DB::transaction(function () use ($case, $userId, $reason, &$newPath): CaseDocument {
                $document = CaseDocument::query()
                    ->where('case_id', $case->id)
                    ->where('document_type', CaseDocument::TYPE_FUNERAL_CONTRACT)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $this->canCorrectBirthDate($case, $document)) {
                    throw ValidationException::withMessages(['contract' => 'Birth date correction is not available for this contract.']);
                }
                if (! $document->file_path || ! Storage::disk('local')->exists($document->file_path)) {
                    throw ValidationException::withMessages(['contract' => 'Original contract PDF is missing. Correction was not issued.']);
                }

                $oldSnapshot = $document->contract_snapshot;
                $newSnapshot = $oldSnapshot;
                data_set($newSnapshot, 'deceased.date_of_birth', $this->birthDate($case));
                $revisionNumber = (int) CaseDocumentRevision::query()
                    ->where('case_document_id', $document->id)->max('revision_number') + 1;
                $fileName = 'funeral-contract-' . Str::slug($document->contract_number ?: (string) $document->id)
                    . '-corrected-' . now()->format('Ymd-His') . '-' . Str::lower(Str::random(6)) . '.pdf';
                $newPath = 'case-documents/' . $case->id . '/' . $fileName;
                $pdf = Pdf::loadView($this->layout->viewName($this->layout->forIssuedContract($newSnapshot)), [
                    'funeral_case' => $case,
                    'generatedAt' => now(),
                    'contractNumber' => $document->contract_number,
                    'contractSnapshot' => $newSnapshot,
                    'contractView' => $this->mapper->map($newSnapshot),
                ])->setPaper('a4')->output();

                if (! Storage::disk('local')->put($newPath, $pdf)) {
                    throw ValidationException::withMessages(['contract' => 'Corrected contract PDF could not be saved.']);
                }

                CaseDocumentRevision::create([
                    'case_document_id' => $document->id,
                    'revision_number' => $revisionNumber,
                    'contract_snapshot' => $oldSnapshot,
                    'file_name' => $document->file_name,
                    'file_path' => $document->file_path,
                    'generated_by' => $document->generated_by,
                    'generated_at' => $document->generated_at,
                    'superseded_by' => $userId,
                    'superseded_at' => now(),
                    'reason' => $reason,
                ]);

                $document->fill([
                    'contract_snapshot' => $newSnapshot,
                    'file_name' => $fileName,
                    'file_path' => $newPath,
                    'generated_by' => $userId,
                    'generated_at' => now(),
                ])->save();

                return $document->fresh();
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk('local')->delete($newPath);
            }
            throw $exception;
        }
    }
}
