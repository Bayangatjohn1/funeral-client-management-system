<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CaseDocument;
use App\Models\FuneralCase;
use App\Services\CaseDocument\FuneralContractPdfService;
use App\Services\CaseDocument\FuneralContractValidationService;
use App\Support\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CaseDocumentController extends Controller
{
    public function store(
        FuneralCase $funeral_case,
        FuneralContractValidationService $validationService,
        FuneralContractPdfService $pdfService
    ): RedirectResponse {
        $this->authorize('generateFuneralContract', $funeral_case);

        $missing = $validationService->missingFields($funeral_case);
        if ($missing !== []) {
            return back()->withErrors([
                'contract' => 'Cannot generate Funeral Contract. Complete the missing required information listed below.',
            ])->with('contract_missing_fields', array_values($missing));
        }

        $document = $pdfService->generate($funeral_case, (int) auth()->id());

        AuditLogger::log(
            'case_document.generated',
            'generate',
            'case_document',
            $document->id,
            [
                'case_id' => $funeral_case->id,
                'document_type' => $document->document_type,
                'contract_number' => $document->contract_number,
                'file_name' => $document->file_name,
            ],
            (int) $funeral_case->branch_id,
            null,
            'success',
            'Funeral Contract generated',
            'Funeral Contract generated'
        );

        return back()->with('success', 'Funeral Contract generated successfully.');
    }

    public function preview(FuneralCase $funeral_case, CaseDocument $document): Response
    {
        $this->authorizeDocumentAccess($funeral_case, $document);

        AuditLogger::log(
            'case_document.previewed',
            'view',
            'case_document',
            $document->id,
            ['case_id' => $funeral_case->id, 'document_type' => $document->document_type],
            (int) $funeral_case->branch_id,
            null,
            'success',
            'Funeral Contract previewed',
            'Funeral Contract previewed'
        );

        return $this->inlinePdf($document);
    }

    public function download(FuneralCase $funeral_case, CaseDocument $document)
    {
        $this->authorizeDocumentAccess($funeral_case, $document);

        AuditLogger::log(
            'case_document.downloaded',
            'download',
            'case_document',
            $document->id,
            ['case_id' => $funeral_case->id, 'document_type' => $document->document_type],
            (int) $funeral_case->branch_id,
            null,
            'success',
            'Funeral Contract downloaded',
            'Funeral Contract downloaded'
        );

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->file_name, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function print(FuneralCase $funeral_case, CaseDocument $document): Response
    {
        $this->authorizeDocumentAccess($funeral_case, $document);

        AuditLogger::log(
            'case_document.printed',
            'print',
            'case_document',
            $document->id,
            ['case_id' => $funeral_case->id, 'document_type' => $document->document_type],
            (int) $funeral_case->branch_id,
            null,
            'success',
            'Funeral Contract opened for printing',
            'Funeral Contract printed'
        );

        return $this->inlinePdf($document);
    }

    private function authorizeDocumentAccess(FuneralCase $funeralCase, CaseDocument $document): void
    {
        abort_unless((int) $document->case_id === (int) $funeralCase->id, 404);
        abort_unless($document->isFuneralContract(), 404);

        $this->authorize('view', $funeralCase);
    }

    private function inlinePdf(CaseDocument $document): Response
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return response(Storage::disk('local')->get($document->file_path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name) . '"',
        ]);
    }
}
