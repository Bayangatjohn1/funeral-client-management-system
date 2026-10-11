<?php

namespace Tests\Unit;

use App\Services\CaseDocument\FuneralContractLayout;
use App\Services\CaseDocument\FuneralContractMapper;
use Barryvdh\DomPDF\Facade\Pdf;
use Tests\TestCase;

class FuneralContractLayoutTest extends TestCase
{
    public function test_standard_package_uses_condensed_layout(): void
    {
        $layout = new FuneralContractLayout();

        $this->assertSame(FuneralContractLayout::CONDENSED, $layout->forNewContract([
            'automatic_charges' => [],
            'add_ons' => [],
            'itemized_services' => [],
        ], ['legacy' => false, 'additional_services' => []]));
        $this->assertSame('pdf.funeral_contract_condensed', $layout->viewName(FuneralContractLayout::CONDENSED));
    }

    public function test_any_additional_service_or_historical_case_uses_detailed_layout(): void
    {
        $layout = new FuneralContractLayout();

        foreach (['automatic_charges', 'add_ons', 'itemized_services'] as $key) {
            $this->assertSame(FuneralContractLayout::DETAILED, $layout->forNewContract([$key => [['amount' => 1]]]));
        }

        $this->assertSame(FuneralContractLayout::DETAILED, $layout->forNewContract([], ['additional_services' => [['amount' => 1]]]));
        $this->assertSame(FuneralContractLayout::DETAILED, $layout->forNewContract([], ['legacy' => true]));
        $this->assertSame(FuneralContractLayout::DETAILED, $layout->forIssuedContract([]));
        $this->assertSame(FuneralContractLayout::CONDENSED, $layout->forIssuedContract(['document_layout' => 'condensed']));
    }

    public function test_condensed_layout_renders_as_pdf_without_sample_contract_values(): void
    {
        $snapshot = [
            'contract_number' => 'FCN-2026-000123',
            'contract_date' => '2026-10-06',
            'case' => ['case_code' => 'FC0123'],
            'branch' => ['name' => 'Test Branch'],
            'client' => ['name' => 'Test Client'],
            'deceased' => ['name' => 'Test Deceased'],
            'package' => ['name' => 'Test Package', 'base_price' => 50000],
            'totals' => ['subtotal' => 50000, 'total' => 50000, 'balance' => 50000],
            'payment' => ['total_paid' => 0],
            'stipulations' => [],
        ];
        $contractView = app(FuneralContractMapper::class)->map($snapshot);
        $html = view('pdf.funeral_contract_condensed', [
            'contractSnapshot' => $snapshot,
            'contractView' => $contractView,
        ])->render();
        $pdf = Pdf::loadView('pdf.funeral_contract_condensed', [
            'contractSnapshot' => $snapshot,
            'contractView' => $contractView,
        ])->setPaper('a4')->output();

        $this->assertStringContainsString('CHARGE TO', $html);
        $this->assertStringContainsString('Sabangan-Caguioa logo', $html);
        $this->assertStringContainsString('Zone VI Poblacion, Bayambang, Pangasinan', $html);
        $this->assertStringContainsString('ROEL S. CAGUIOA - Prop.', $html);
        $this->assertStringContainsString('DAYS OF EMBALMING', $html);
        $this->assertStringContainsString('DOWN PAYMENT', $html);
        $this->assertStringContainsString('Lagda ng Nangako', $html);
        $this->assertStringNotContainsString('Not configured', $html);
        $this->assertStringStartsWith('%PDF', $pdf);
    }
}
