<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\PaymentProofUpload;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentProofController extends Controller
{
    public function store(Request $request)
    {
        abort_unless($request->user()->isStaff() || $request->user()->isAdmin(), 403);
        $data = $request->validate(['form_id' => ['required', 'uuid'], 'context' => ['required', 'in:intake,payment,attachment,senior,deceased,tarpaulin,catalog']]);
        if ($data['context'] === 'catalog') abort_unless($request->user()->isMainAdmin(), 403);
        $token = Str::random(64);
        $upload = PaymentProofUpload::create([
            'user_id' => $request->user()->id, 'session_hash' => \App\Support\Payments\PaymentProof::sessionHash($request),
            'form_id' => $data['form_id'], 'context' => $data['context'], 'token_hash' => hash('sha256', $token),
            'expires_at' => now()->addMinutes(5), 'retain_until' => now()->addDay(), 'disk' => 'local',
        ]);
        $url = URL::temporarySignedRoute('payment-proof.phone', $upload->expires_at, ['upload' => $upload->id, 'token' => $token]);
        $svg = (new Writer(new ImageRenderer(new RendererStyle(224), new SvgImageBackEnd)))->writeString($url);

        return response()->json([
            'id' => $upload->id, 'expires_at' => $upload->expires_at->toIso8601String(),
            'qr' => 'data:image/svg+xml;base64,'.base64_encode($svg),
        ])->header('Cache-Control', 'no-store');
    }

    private function owned(Request $request, PaymentProofUpload $upload): void
    {
        abort_unless((int) $upload->user_id === (int) $request->user()->id
            && hash_equals($upload->session_hash, \App\Support\Payments\PaymentProof::sessionHash($request)), 403);
        if ($upload->context === 'catalog') abort_unless($request->user()->isMainAdmin(), 403);
    }

    private function phoneAllowed(Request $request, PaymentProofUpload $upload): void
    {
        abort_unless(hash_equals($upload->token_hash, hash('sha256', (string) $request->query('token'))), 403);
        abort_if($upload->expires_at->isPast() || $upload->cancelled_at || $upload->claimed_at || $upload->path, 410,
            'This upload link has expired or has already been used. Generate a new QR on the desktop.');
        abort_unless(\App\Models\User::whereKey($upload->user_id)->where('is_active', true)->exists(), 410);
        if ($upload->context === 'catalog') abort_unless($upload->user?->isMainAdmin(), 410);
    }

    public function phone(Request $request, PaymentProofUpload $upload)
    {
        $this->phoneAllowed($request, $upload);
        return response()->view('payment-proof.phone', ['upload' => $upload])->withHeaders([
            'Cache-Control' => 'no-store', 'Referrer-Policy' => 'no-referrer',
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function phoneUpload(Request $request, PaymentProofUpload $upload)
    {
        return $this->receive($request, $upload, true);
    }

    public function desktopUpload(Request $request, PaymentProofUpload $upload)
    {
        $this->owned($request, $upload);
        return $this->receive($request, $upload, false);
    }

    private function receive(Request $request, PaymentProofUpload $upload, bool $phone)
    {
        $this->validateProof($request, $upload->context);
        $path = null;
        try {
            DB::transaction(function () use ($request, $upload, $phone, &$path) {
                $locked = PaymentProofUpload::whereKey($upload->id)->lockForUpdate()->firstOrFail();
                if ($phone) $this->phoneAllowed($request, $locked);
                else {
                    $this->owned($request, $locked);
                    abort_if($locked->expires_at->isPast() || $locked->cancelled_at || $locked->claimed_at || $locked->path, 410);
                }
                $file = $request->file('proof');
                $path = $file->store($locked->context === 'catalog' ? 'catalog-photo-uploads' : 'payment-proofs', $locked->disk);
                if (! $path) throw ValidationException::withMessages(['proof' => 'The proof could not be stored. Try again.']);
                $locked->update([
                    'path' => $path, 'mime' => (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()), 'size' => $file->getSize(),
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''),
                    'retain_until' => now()->addDay(),
                ]);
            });
        } catch (\Throwable $error) {
            if ($path) Storage::disk($upload->disk)->delete($path);
            throw $error;
        }
        return response()->json(['message' => $upload->context === 'catalog' ? 'Photo sent. Review it on the desktop before saving.' : 'Proof sent. Check the desktop form before saving the payment.']);
    }

    private function validateProof(Request $request, string $context): void
    {
        $formats = match ($context) {
            'senior' => ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'],
            'deceased' => ['image/jpeg', 'image/png', 'image/webp'],
            'tarpaulin', 'catalog' => ['image/jpeg', 'image/png'],
            default => ['image/jpeg', 'image/png', 'application/pdf'],
        };
        $maxSize = $context === 'deceased' ? 3072 : 5120;
        if ($context === 'catalog') $request->validate(['proof' => ['required', 'file', 'image', 'mimetypes:image/jpeg,image/png', 'max:5120', 'dimensions:max_width=8000,max_height=8000']]);
        $request->validate(['proof' => ['bail', 'required', 'file', 'max:'.$maxSize, function ($attribute, $file, $fail) use ($formats) {
            if (! in_array((new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath()), $formats, true)) {
                $fail('Choose a supported file type for this attachment.');
            }
        }]]);
    }

    public function attach(Request $request, Payment $payment)
    {
        $user = $request->user();
        DB::transaction(function () use ($request, $payment, $user) {
            $locked = Payment::lockForUpdate()->findOrFail($payment->id);
            abort_unless($user->isStaff()
                && (int) $user->branch_id === (int) $locked->branch_id
                && (int) $user->id === (int) ($locked->recorded_by ?? $locked->encoded_by ?? $locked->received_by), 403);
            abort_if(in_array($locked->status, ['VOID', 'VOIDED'], true)
                || $locked->funeralCase?->entry_source === 'OTHER_BRANCH', 422);
            abort_if($locked->proof_path || $locked->proof_upload_id, 409, 'This payment already has proof.');

            $request->validate([
                'payment_proof_id' => ['required', 'uuid'],
                'payment_proof_form_id' => ['required', 'uuid'],
            ]);
            \App\Support\Payments\PaymentProof::claim($request, $locked, 'attachment');
            \App\Support\AuditLogger::log('PAYMENT_PROOF_ATTACHED', 'UPDATE', 'payment', $locked->id,
                ['proof_upload_id' => $locked->proof_upload_id], $locked->branch_id);
        });

        return back()->with('success', 'Proof attached to the payment. You can now request a correction.');
    }

    public function status(Request $request, PaymentProofUpload $upload)
    {
        $this->owned($request, $upload);
        $state = $upload->cancelled_at ? 'cancelled' : ($upload->claimed_at ? 'saved' : ($upload->retain_until->isPast()
            ? 'expired' : ($upload->path ? 'received' : ($upload->expires_at->isPast() ? 'expired' : 'waiting'))));
        return response()->json([
            'state' => $state, 'name' => $upload->original_name, 'mime' => $upload->mime,
            'payment_url' => $state === 'saved' && ($payment = Payment::where('proof_upload_id', $upload->id)->first())
                ? route('payments.summary', $payment) : null,
            'expires_at' => $upload->expires_at->toIso8601String(),
            'preview' => $state === 'received' ? route('payment-proof.preview', $upload) : null,
        ])->header('Cache-Control', 'no-store');
    }

    public function cancel(Request $request, PaymentProofUpload $upload)
    {
        $this->owned($request, $upload);
        DB::transaction(function () use ($upload) {
            $locked = PaymentProofUpload::whereKey($upload->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->claimed_at, 409, 'This proof is already attached to a saved payment.');
            $locked->update(['cancelled_at' => now(), 'retain_until' => now()]);
        });
        return response()->json(['message' => 'Upload cancelled.']);
    }

    private function file(PaymentProofUpload $upload)
    {
        abort_unless($upload->path && Storage::disk($upload->disk)->exists($upload->path), 404);
        return Storage::disk($upload->disk)->response($upload->path, 'payment-proof.'.($upload->mime === 'application/pdf' ? 'pdf' : ($upload->mime === 'image/png' ? 'png' : ($upload->mime === 'image/webp' ? 'webp' : 'jpg'))), [
            'Content-Type' => $upload->mime, 'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff', 'Referrer-Policy' => 'no-referrer',
        ]);
    }

    public function preview(Request $request, PaymentProofUpload $upload)
    {
        $this->owned($request, $upload);
        abort_if($upload->cancelled_at || $upload->claimed_at || $upload->retain_until->isPast(), 410);
        return $this->file($upload);
    }

    public function payment(Request $request, Payment $payment)
    {
        abort_unless(($request->user()->isStaff() || $request->user()->isAdmin())
            && in_array((int) $payment->branch_id, array_map('intval', $request->user()->branchScopeIds()), true), 403);
        abort_unless($payment->proof_path && $payment->proof_mime, 404);
        return $this->file(new PaymentProofUpload(['path' => $payment->proof_path, 'mime' => $payment->proof_mime, 'disk' => 'local']));
    }
}
