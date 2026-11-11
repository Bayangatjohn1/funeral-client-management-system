<?php

namespace App\Support\Payments;

use App\Models\Payment;
use App\Models\PaymentProofUpload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class PaymentProof
{
    public static function sessionHash(Request $request): string
    {
        $key = $request->session()->get('payment_proof_session');
        if (! $key) {
            $key = \Illuminate\Support\Str::random(64);
            $request->session()->put('payment_proof_session', $key);
        }
        return hash('sha256', $key);
    }

    public static function claim(Request $request, Payment $payment, string $context): void
    {
        // Call inside the payment transaction so retries cannot consume a proof twice.
        $upload = PaymentProofUpload::whereKey($request->input('payment_proof_id'))->lockForUpdate()->first();
        if (! $upload || (int) $upload->user_id !== (int) $request->user()->id
            || ! hash_equals($upload->session_hash, self::sessionHash($request))
            || $upload->form_id !== $request->input('payment_proof_form_id') || $upload->context !== $context
            || $upload->cancelled_at || $upload->claimed_at || $upload->retain_until->isPast()
            || ! $upload->path || ! Storage::disk($upload->disk)->exists($upload->path)) {
            throw ValidationException::withMessages(['payment_proof_id' => 'Attach a payment proof for this form before saving.']);
        }
        $payment->update([
            'proof_upload_id' => $upload->id, 'proof_path' => $upload->path,
            'proof_mime' => $upload->mime, 'proof_name' => $upload->original_name,
        ]);
        $upload->update(['claimed_at' => now()]);
    }
}
