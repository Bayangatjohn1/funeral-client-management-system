<?php

namespace App\Services\CaseDocument;

use App\Models\CaseDocument;
use App\Models\FuneralCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FuneralContractPdfService
{
    public function generate(FuneralCase $case, int $userId): CaseDocument
    {
        $case->loadMissing([
            'branch',
            'client',
            'deceased',
            'package.packageInclusions',
            'package.packageFreebies',
            'caseAddOns',
            'payments.encodedBy',
            'payments.recordedBy',
            'serviceDetail',
        ]);

        $generatedAt = now();
        $existingDocument = CaseDocument::query()
            ->where('case_id', $case->id)
            ->where('document_type', CaseDocument::TYPE_FUNERAL_CONTRACT)
            ->first();
        $contractNumber = $existingDocument?->contract_number ?: $this->contractNumber($case, $generatedAt);
        $fileName = $this->fileName($case, $generatedAt, $contractNumber);
        $filePath = 'case-documents/' . $case->id . '/' . $fileName;

        $pdfBinary = Pdf::loadView('pdf.funeral_contract', [
            'funeral_case' => $case,
            'generatedAt' => $generatedAt,
            'contractNumber' => $contractNumber,
        ])->setPaper('a4')->output();

        Storage::disk('local')->put($filePath, $pdfBinary);

        try {
            return DB::transaction(function () use ($case, $userId, $generatedAt, $contractNumber, $fileName, $filePath) {
                $document = CaseDocument::where('case_id', $case->id)
                    ->where('document_type', CaseDocument::TYPE_FUNERAL_CONTRACT)
                    ->lockForUpdate()
                    ->first();

                $oldPath = $document?->file_path;

                if (! $document) {
                    $document = new CaseDocument([
                        'case_id' => $case->id,
                        'document_type' => CaseDocument::TYPE_FUNERAL_CONTRACT,
                    ]);
                }

                $document->fill([
                    'contract_number' => $document->contract_number ?: $contractNumber,
                    'file_name' => $fileName,
                    'file_path' => $filePath,
                    'generated_by' => $userId,
                    'generated_at' => $generatedAt,
                ])->save();

                if ($oldPath && $oldPath !== $filePath) {
                    Storage::disk('local')->delete($oldPath);
                }

                return $document->fresh(['generator']);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($filePath);
            throw $exception;
        }
    }

    private function contractNumber(FuneralCase $case, mixed $generatedAt): string
    {
        return 'FCN-' . $generatedAt->format('Y') . '-' . str_pad((string) $case->id, 6, '0', STR_PAD_LEFT);
    }

    private function fileName(FuneralCase $case, mixed $generatedAt, string $contractNumber): string
    {
        $caseCode = Str::slug((string) ($case->case_code ?: 'case-' . $case->id));
        $reference = Str::slug($contractNumber);

        return 'funeral-contract-' . $reference . '-' . $caseCode . '-' . $generatedAt->format('Ymd-His') . '.pdf';
    }
}
