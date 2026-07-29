<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\CaseDocument;
use App\Models\FuneralCase;
use App\Services\CaseDocument\FuneralContractMapper;
use App\Services\CaseDocument\FuneralContractPdfService;
use App\Services\CaseDocument\FuneralContractSnapshotService;
use App\Services\CaseDocument\FuneralContractValidationService;
use App\Support\AuditLogger;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class CaseDocumentController extends Controller
{
    public function contractPreview(
        FuneralCase $funeral_case,
        FuneralContractValidationService $validationService,
        FuneralContractSnapshotService $snapshotService,
        FuneralContractMapper $mapper
    ) {
        $this->authorize('generateFuneralContract', $funeral_case);

        $missing = $validationService->missingFields($funeral_case);
        if ($missing !== []) {
            return back()->withErrors([
                'contract' => 'Cannot prepare Funeral Contract. Complete the missing required information listed below.',
            ])->with('contract_missing_fields', array_values($missing));
        }

        $funeral_case->loadMissing(['funeralContract.generator']);

        $contractSnapshot = $snapshotService->previewData($funeral_case);
        $contractView = $mapper->map($contractSnapshot);
        $pricingMismatch = ! (bool) data_get($contractView, 'reconciliation.reconciled', true);

        return view('staff.case_documents.contract_preview', [
            'funeral_case' => $funeral_case,
            'contractSnapshot' => $contractSnapshot,
            'contractView' => $contractView,
            'existingDocument' => $funeral_case->funeralContract,
            'pricingMismatch' => $pricingMismatch,
        ]);
    }

    public function store(
        Request $request,
        FuneralCase $funeral_case,
        FuneralContractValidationService $validationService,
        FuneralContractPdfService $pdfService,
        FuneralContractSnapshotService $snapshotService,
        FuneralContractMapper $mapper
    ): RedirectResponse {
        $this->authorize('generateFuneralContract', $funeral_case);

        $missing = $validationService->missingFields($funeral_case);
        if ($missing !== []) {
            return back()->withErrors([
                'contract' => 'Cannot generate Funeral Contract. Complete the missing required information listed below.',
            ])->with('contract_missing_fields', array_values($missing));
        }

        $validated = $request->validate([
            'interment_time' => ['nullable', 'date_format:H:i'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ]);

        $contractView = $mapper->map($snapshotService->previewData($funeral_case));
        if (! (bool) data_get($contractView, 'reconciliation.reconciled', true)) {
            return back()
                ->withInput()
                ->withErrors([
                    'contract' => 'The saved pricing breakdown does not match the case total. Please review the case billing record before finalizing the contract.',
                ]);
        }

        $document = $pdfService->generate($funeral_case, (int) auth()->id(), $validated);

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

        return redirect()
            ->route('funeral-cases.show', $funeral_case)
            ->with('success', 'Funeral Contract saved and generated successfully.');
    }

    public function contractPreviewPdf(
        FuneralCase $funeral_case,
        FuneralContractValidationService $validationService,
        FuneralContractSnapshotService $snapshotService,
        FuneralContractMapper $mapper
    ): Response {
        $this->authorize('generateFuneralContract', $funeral_case);

        $missing = $validationService->missingFields($funeral_case);
        abort_if($missing !== [], 422, 'Cannot render Funeral Contract preview until required case information is complete.');

        $snapshot = $snapshotService->previewData($funeral_case);
        $contractView = $mapper->map($snapshot);
        $pdfBinary = Pdf::loadView('pdf.funeral_contract_physical', [
            'funeral_case' => $funeral_case,
            'generatedAt' => now(),
            'contractNumber' => data_get($contractView, 'contract_number'),
            'contractSnapshot' => $snapshot,
            'contractView' => $contractView,
        ])->setPaper('a4')->output();

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="funeral-contract-preview.pdf"',
        ]);
    }

    public function preview(FuneralCase $funeral_case, CaseDocument $document, FuneralContractMapper $mapper): Response
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

        if ($document->isFuneralContract() && is_array($document->contract_snapshot) && $document->contract_snapshot !== []) {
            $snapshot = $document->contract_snapshot;
            $pdfBinary = Pdf::loadView('pdf.funeral_contract_physical', [
                'funeral_case' => $funeral_case,
                'generatedAt' => $document->generated_at ?: now(),
                'contractNumber' => $document->contract_number,
                'contractSnapshot' => $snapshot,
                'contractView' => $mapper->map($snapshot),
            ])->setPaper('a4')->output();

            return response($pdfBinary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . addslashes($document->file_name ?: 'funeral-contract-preview.pdf') . '"',
            ]);
        }

        return $this->inlinePdf($document);
    }

    public function download(FuneralCase $funeral_case, CaseDocument $document, FuneralContractMapper $mapper)
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

        if ($document->isFuneralContract() && is_array($document->contract_snapshot) && $document->contract_snapshot !== []) {
            return $this->snapshotPdfResponse($funeral_case, $document, $mapper, 'attachment');
        }

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

        return response()->view('staff.case_documents.print_contract', [
            'funeral_case' => $funeral_case,
            'document' => $document,
            'pdfUrl' => route('funeral-cases.documents.preview', [$funeral_case, $document], absolute: false),
        ]);
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

    private function snapshotPdfResponse(FuneralCase $funeralCase, CaseDocument $document, FuneralContractMapper $mapper, string $disposition): Response
    {
        $snapshot = $document->contract_snapshot;
        $pdfBinary = Pdf::loadView('pdf.funeral_contract_physical', [
            'funeral_case' => $funeralCase,
            'generatedAt' => $document->generated_at ?: now(),
            'contractNumber' => $document->contract_number,
            'contractSnapshot' => $snapshot,
            'contractView' => $mapper->map($snapshot),
        ])->setPaper('a4')->output();

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . addslashes($document->file_name ?: 'funeral-contract.pdf') . '"',
        ]);
    }
}
