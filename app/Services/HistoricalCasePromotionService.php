<?php

namespace App\Services;

use App\Models\CaseAttachment;
use App\Models\Client;
use App\Models\Deceased;
use App\Models\FuneralCase;
use App\Models\HistoricalCaseSubmission;
use App\Models\Payment;
use App\Models\ServiceDetail;
use App\Models\User;
use App\Support\CeremonyLocation;
use App\Support\Payments\PaymentIdentifier;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HistoricalCasePromotionService
{
    public function approve(HistoricalCaseSubmission $submission, User $reviewer): FuneralCase
    {
        return DB::transaction(function () use ($submission, $reviewer) {
            $submission = HistoricalCaseSubmission::whereKey($submission->id)->lockForUpdate()->firstOrFail();
            if ($submission->status !== HistoricalCaseSubmission::PENDING) {
                throw ValidationException::withMessages(['submission' => 'Only pending historical submissions can be approved.']);
            }
            if ((int) $submission->encoded_by === (int) $reviewer->id) {
                throw ValidationException::withMessages(['submission' => 'You cannot approve a historical entry that you submitted.']);
            }

            $data = $submission->submission_data;
            $v = $data['validated'];
            $pricing = $data['pricing'];
            $schedule = $data['schedule'];
            $historicalPayments = $data['historical_payments'] ?? [];
            if ($historicalPayments === [] && !empty($data['mark_as_paid'])) {
                $historicalPayments[] = [
                    'amount' => (float) ($v['amount_paid'] ?? 0),
                    'paid_at' => $v['paid_at'] ?? now()->toDateTimeString(),
                    'receipt_or_no' => $v['receipt_or_no'] ?? null,
                    'details' => $data['payment_details'] ?? [],
                    'proof' => $data['historical_payment_proof'] ?? null,
                ];
            }
            $branchId = (int) $submission->branch_id;
            \App\Models\Branch::whereKey($branchId)->lockForUpdate()->firstOrFail();
            $nextCodeNumber = (int) (FuneralCase::where('branch_id', $branchId)->pluck('case_code')
                ->map(fn ($code) => (int) preg_replace('/\D+/', '', (string) $code))->max() ?? 0) + 1;
            $client = !empty($data['existing_client_id'])
                ? Client::where('branch_id', $branchId)->lockForUpdate()->findOrFail($data['existing_client_id'])
                : Client::create([
                    'branch_id' => $branchId,
                    'first_name' => $v['client_first_name'], 'middle_name' => $v['client_middle_name'] ?? null,
                    'last_name' => $v['client_last_name'], 'suffix' => $v['client_suffix'] ?? null,
                    'relationship_to_deceased' => $v['client_relationship'],
                    'contact_number' => $v['client_contact_number'] ?? null, 'email' => $v['client_email'] ?? null,
                    'valid_id_type' => $v['client_valid_id_type'] ?? null, 'valid_id_number' => $v['client_valid_id_number'] ?? null,
                    'address' => $v['client_address'],
                ]);

            $intermentAt = Carbon::parse($schedule['interment']);
            $deceased = Deceased::create([
                'branch_id' => $branchId, 'client_id' => $client->id, 'address' => $v['deceased_address'],
                'first_name' => $v['deceased_first_name'], 'middle_name' => $v['deceased_middle_name'] ?? null,
                'last_name' => $v['deceased_last_name'], 'suffix' => $v['deceased_suffix'] ?? null,
                'born' => $v['born'] ?? null, 'died' => $v['died'] ?? null, 'date_of_death' => $v['died'] ?? null,
                'civil_status' => $v['civil_status'] ?? null, 'age' => $data['age'],
                'interment' => $intermentAt->toDateString(), 'interment_at' => $intermentAt,
                'wake_days' => $data['wake_days'], 'place_of_cemetery' => $v['place_of_cemetery'],
                'senior_citizen_status' => (bool) $v['senior_citizen_status'],
                'senior_citizen_id_number' => $v['senior_citizen_id_number'] ?? null,
                'pwd_status' => (bool) ($v['pwd_status'] ?? false), 'pwd_id_number' => $v['pwd_id_number'] ?? null,
                'photo_path' => $data['photo_path'], 'senior_proof_path' => $data['senior_proof_path'],
            ]);

            usort($historicalPayments, fn ($a, $b) => strcmp((string) ($a['paid_at'] ?? ''), (string) ($b['paid_at'] ?? '')));
            $paid = round(collect($historicalPayments)->sum(fn ($payment) => (float) ($payment['amount'] ?? 0)), 2);
            $total = (float) $pricing['total'];
            $balance = round(max($total - $paid, 0), 2);
            $paymentStatus = $paid <= 0 ? 'UNPAID' : ($balance > 0 ? 'PARTIAL' : 'PAID');
            $serviceDate = Carbon::parse($v['wake_start_date'])->toDateString();
            $funeralCase = FuneralCase::create([
                'branch_id' => $branchId, 'client_id' => $client->id,
                'client_relationship' => $v['client_relationship'], 'deceased_id' => $deceased->id,
                'package_id' => $data['selected_package_id'], 'hearse_catalog_id' => $data['hearse_id'],
                'hearse_name_snapshot' => $data['hearse_name'], 'package_name_snapshot' => $data['service_package_name'],
                'package_price_snapshot' => $data['package_price'],
                'package_inclusions_snapshot' => $data['package_inclusions'],
                'package_freebies_snapshot' => $data['package_freebies'],
                'package_promo_snapshot' => $data['package_promo_snapshot'],
                'package_discount_snapshot' => $pricing['discount_amount'], 'pricing_snapshot' => $pricing['snapshot'],
                'case_number' => FuneralCase::nextCaseNumber($branchId),
                'case_code' => 'FC'.str_pad((string) $nextCodeNumber, 4, '0', STR_PAD_LEFT),
                'custom_package_name' => $data['is_custom'] ? $data['service_package_name'] : null,
                'custom_package_price' => $data['is_custom'] ? $data['package_price'] : 0,
                'custom_package_inclusions' => $data['is_custom'] ? ($v['custom_package_inclusions'] ?? null) : null,
                'custom_package_freebies' => $data['is_custom'] ? ($v['custom_package_freebies'] ?? null) : null,
                'service_type' => $v['service_type'], 'service_requested_at' => $serviceDate,
                'service_package' => $data['service_package_name'], 'coffin_type' => $data['coffin_type'],
                'wake_location' => $v['wake_location'], 'viewing_type' => $v['viewing_type'] ?? null,
                'chapel_address' => $v['chapel_address'] ?? null, 'chapel_catalog_id' => $v['chapel_catalog_id'] ?? null,
                'wake_start_date' => $schedule['wake_start_date'], 'wake_start_time' => $schedule['wake_start_time'],
                'wake_end_date' => $schedule['wake_end_date'], 'wake_end_time' => $schedule['wake_end_time'],
                'funeral_service_at' => $schedule['funeral_date'], 'funeral_service_time' => $schedule['funeral_time'],
                ...CeremonyLocation::attributes($v),
                'interment_at' => $intermentAt, 'interment_time' => $schedule['interment_time'],
                'transport_option' => $v['transport_option'] ?? null, 'transport_notes' => $v['transport_notes'] ?? null,
                'coffin_length_cm' => $v['coffin_length_cm'] ?? null, 'coffin_size' => $v['coffin_size'] ?? null,
                'embalming_required' => (bool) ($v['embalming_required'] ?? false),
                'embalming_status' => $v['embalming_status'] ?? null,
                'embalming_at' => $v['embalming_at'] ?? null, 'embalming_notes' => $v['embalming_notes'] ?? null,
                'additional_services' => $v['additional_services'] ?? null,
                'additional_service_amount' => $data['additional_service_amount'],
                'add_ons_total_amount' => $data['add_ons_total'], 'subtotal_amount' => $pricing['subtotal'],
                'discount_type' => $pricing['discount']['discount_type'] ?? null,
                'discount_value_type' => $pricing['discount']['discount_value_type'] ?? null,
                'discount_value' => $pricing['discount']['discount_value'] ?? 0,
                'discount_amount' => $pricing['discount_amount'], 'tax_rate' => $pricing['tax_rate'],
                'tax_amount' => $pricing['tax_amount'], 'discount_note' => $pricing['discount']['discount_note'] ?? null,
                'total_amount' => $total, 'total_paid' => $paid, 'balance_amount' => $balance,
                'payment_status' => $paymentStatus, 'initial_payment_type' => $paid >= $total && $paid > 0 ? 'FULL' : ($paid > 0 ? 'PARTIAL' : null),
                'paid_at' => $historicalPayments ? Carbon::parse(end($historicalPayments)['paid_at']) : null,
                'case_status' => 'COMPLETED', 'completed_at' => now(),
                'reported_branch_id' => $branchId, 'reported_at' => now(), 'encoded_by' => $submission->encoded_by,
                'entry_source' => 'MAIN', 'verification_status' => 'VERIFIED', 'verified_by' => $reviewer->id,
                'verified_at' => now(), 'verification_note' => 'Historical case approved.',
            ]);

            foreach ([
                [CaseAttachment::TYPE_DECEASED_PHOTO, $data['photo_path'], $data['photo_file_name']],
                [CaseAttachment::TYPE_SENIOR_PROOF, $data['senior_proof_path'], $data['senior_proof_file_name']],
            ] as [$type, $path, $name]) {
                if ($path) $funeralCase->attachments()->create(['attachment_type' => $type, 'file_name' => $name ?: basename($path), 'file_path' => $path, 'uploaded_by' => $submission->encoded_by]);
            }
            ServiceDetail::create([
                'funeral_case_id' => $funeralCase->id, 'start_of_wake' => $schedule['wake_start_date'],
                'internment_date' => $intermentAt->toDateString(), 'wake_days' => $data['wake_days'],
                'wake_location' => $v['wake_location'], 'cemetery_place' => $v['place_of_cemetery'], 'case_status' => 'completed',
            ]);
            foreach ($data['selected_add_ons'] as $addon) {
                $funeralCase->caseAddOns()->create($addon);
            }

            $runningPaid = 0.0;
            foreach ($historicalPayments as $historyPayment) {
                $details = $historyPayment['details'] ?? [];
                $paidAt = Carbon::parse($historyPayment['paid_at']);
                $receipt = $historyPayment['receipt_or_no'] ?? null;
                foreach ([['receipt_or_no', $receipt], ['reference_number', $details['reference_number'] ?? null], ['approval_code', $details['approval_code'] ?? null]] as [$type, $value]) {
                    if ($value && ($duplicate = PaymentIdentifier::duplicate($branchId, $type, $value, $details['cashless_type'] ?? null, PaymentIdentifier::providerFor($details)))) {
                        throw ValidationException::withMessages(['submission' => PaymentIdentifier::duplicateMessage($type, $duplicate)]);
                    }
                }
                $runningPaid = round($runningPaid + (float) $historyPayment['amount'], 2);
                $runningBalance = round(max($total - $runningPaid, 0), 2);
                $runningStatus = $runningPaid <= 0 ? 'UNPAID' : ($runningBalance > 0 ? 'PARTIAL' : 'PAID');
                $payment = Payment::create([
                    'funeral_case_id' => $funeralCase->id, 'branch_id' => $branchId,
                    'payment_record_no' => Payment::nextPaymentRecordNumber($paidAt),
                    'receipt_or_no' => $receipt, 'accounting_reference_no' => $receipt,
                    'method' => $details['legacy_method'] ?? 'CASH', 'payment_mode' => $details['legacy_payment_mode'] ?? 'cash',
                    'payment_method' => $details['payment_method'] ?? 'CASH', 'cashless_type' => $details['cashless_type'] ?? null,
                    'bank_name' => $details['bank_name'] ?? null, 'other_bank_name' => $details['other_bank_name'] ?? null,
                    'wallet_provider' => $details['wallet_provider'] ?? null, 'account_name' => $details['account_name'] ?? null,
                    'mobile_number' => $details['mobile_number'] ?? null, 'reference_number' => $details['reference_number'] ?? null,
                    'approval_code' => $details['approval_code'] ?? null, 'card_type' => $details['card_type'] ?? null,
                    'terminal_provider' => $details['terminal_provider'] ?? null, 'payment_channel' => $details['payment_channel'] ?? null,
                    'payment_notes' => $details['payment_notes'] ?? null, 'bank_or_channel' => $details['legacy_bank_or_channel'] ?? null,
                    'other_bank_or_channel' => $details['legacy_other_bank_or_channel'] ?? null,
                    'transaction_reference_no' => $details['legacy_transaction_reference_no'] ?? null,
                    'sender_name' => $details['legacy_sender_name'] ?? null, 'amount' => (float) $historyPayment['amount'],
                    'balance_after_payment' => $runningBalance, 'payment_status_after_payment' => $runningStatus,
                    'paid_date' => $paidAt->toDateString(), 'paid_at' => $paidAt,
                    'encoded_by' => $submission->encoded_by, 'recorded_by' => $submission->encoded_by,
                    'proof_path' => $historyPayment['proof']['path'] ?? null,
                    'proof_mime' => $historyPayment['proof']['mime'] ?? null,
                    'proof_name' => $historyPayment['proof']['name'] ?? null,
                ]);
                $payment->update(['receipt_number' => $payment->payment_record_no]);
            }

            $submission->update([
                'status' => HistoricalCaseSubmission::APPROVED, 'official_case_id' => $funeralCase->id,
                'authorized_by' => $reviewer->id, 'authorized_at' => now(),
            ]);
            $submission->reviews()->create(['reviewed_by' => $reviewer->id, 'action' => 'APPROVED', 'note' => null, 'created_at' => now()]);
            return $funeralCase;
        });
    }
}
