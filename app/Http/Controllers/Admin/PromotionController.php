<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\Promotion;
use App\Support\AuditLogger;
use App\Support\CatalogFormResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Validator;

class PromotionController extends Controller
{
    public function create()
    {
        $this->ensureCanManage();
        return view('admin.promotions.form', ['promotion' => new Promotion(['is_enabled' => true])]);
    }

    public function store(Request $request)
    {
        $this->ensureCanManage();
        $validated = $this->validatedPromotion($request);
        $promotion = Promotion::create([
            ...$validated,
            'is_enabled' => true,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);
        AuditLogger::log('promotion.created', 'create', 'promotion', $promotion->id, [
            'name' => $promotion->name,
            'discount_type' => $promotion->discount_type,
            'discount_value' => $promotion->discount_value,
        ], actionLabel: 'Promotion created');
        return CatalogFormResponse::saved($request, '“'.$promotion->name.'” was saved successfully.', route('admin.service-management.index', ['tab' => 'promotions']));
    }

    public function show(Promotion $promotion)
    {
        $this->ensureCanView();
        $promotion->load('package');
        return view('admin.promotions.show', ['promotion' => $promotion, 'status' => $promotion->statusAt(now('Asia/Manila'), $promotion->package)]);
    }

    public function edit(Promotion $promotion)
    {
        $this->ensureCanManage();
        return view('admin.promotions.form', ['promotion' => $promotion]);
    }

    public function update(Request $request, Promotion $promotion)
    {
        $this->ensureCanManage();
        $validated = $this->validatedPromotion($request, $promotion);
        $before = $promotion->only(['name', 'discount_type', 'discount_value', 'starts_at', 'ends_at']);
        $promotion->fill([...$validated, 'updated_by' => $request->user()->id])->save();
        AuditLogger::log('promotion.updated', 'update', 'promotion', $promotion->id, [
            'before' => $before,
            'after' => $promotion->only(['name', 'discount_type', 'discount_value', 'starts_at', 'ends_at']),
            'status' => $promotion->statusAt(now('Asia/Manila'), $promotion->package),
        ], actionLabel: 'Promotion updated');
        return CatalogFormResponse::saved($request, '“'.$promotion->name.'” was updated successfully.', route('admin.service-management.index', ['tab' => 'promotions']));
    }

    public function stop(Request $request, Promotion $promotion)
    {
        $this->ensureCanManage();
        $request->validate(['confirmed' => ['required', 'accepted']]);
        $promotion->load('package');
        $previous = $promotion->statusAt(now('Asia/Manila'), $promotion->package);
        if (! in_array($previous, ['Active', 'Scheduled'], true)) {
            return back()->with('error', '“'.$promotion->name.'” can only be turned off while active or scheduled.');
        }
        $promotion->forceFill(['is_enabled' => false, 'manually_stopped_at' => now('Asia/Manila'), 'manually_stopped_by' => $request->user()->id, 'updated_by' => $request->user()->id])->save();
        AuditLogger::log('promotion.stopped', 'status_change', 'promotion', $promotion->id, [
            'previous_status' => $previous, 'new_status' => 'Turned Off',
            'manual_stop_at' => $promotion->manually_stopped_at?->toIso8601String(),
        ], actionLabel: 'Promotion turned off');
        return back()->with('success', '“'.$promotion->name.'” was turned off. Its configuration and schedule were retained.');
    }

    public function reactivate(Request $request, Promotion $promotion)
    {
        $this->ensureCanManage();
        $promotion->load('package');
        if (! $promotion->manually_stopped_at) {
            return back()->with('error', '“'.$promotion->name.'” can only be turned on after it has been manually turned off.');
        }
        if ($promotion->statusAt(now('Asia/Manila'), $promotion->package) === 'Expired') {
            return back()->with('error', '“'.$promotion->name.'” has expired. Update its End Date before turning it on again.');
        }
        if ($promotion->reviewFields($promotion->package) !== []) {
            return back()->with('error', 'Review and correct the configuration of “'.$promotion->name.'” before turning it on.');
        }
        $stoppedAt = $promotion->manually_stopped_at;
        $stoppedBy = $promotion->manually_stopped_by;
        $promotion->forceFill(['is_enabled' => true, 'manually_stopped_at' => null, 'manually_stopped_by' => null, 'updated_by' => $request->user()->id])->save();
        $status = $promotion->statusAt(now('Asia/Manila'), $promotion->package);
        AuditLogger::log('promotion.reenabled', 'status_change', 'promotion', $promotion->id, [
            'previous_status' => 'Turned Off', 'new_status' => $status,
            'stopped_at' => $stoppedAt?->toIso8601String(), 'stopped_by' => $stoppedBy,
        ], actionLabel: 'Promotion turned on');
        return back()->with('success', '“'.$promotion->name."” was turned on. Its current status is {$status}.");
    }

    private function rules(?Promotion $promotion = null): array
    {
        return [
            'name' => ['bail', 'required', 'string', 'max:120', "regex:~^[\\pL\\pM\\pN][\\pL\\pM\\pN\\s.,'()/&-]*$~u"],
            'discount_type' => ['bail', 'required', Rule::in(['AMOUNT', 'PERCENT'])],
            'discount_value' => ['bail', 'required', 'numeric', 'gt:0', 'max:9999999999.99', 'decimal:0,2'],
            'starts_at' => ['bail', 'nullable', 'date_format:Y-m-d'],
            'ends_at' => ['bail', 'nullable', 'date_format:Y-m-d', 'after_or_equal:starts_at'],
        ];
    }

    private function validatedPromotion(Request $request, ?Promotion $promotion = null): array
    {
        $validator = Validator::make($request->all(), $this->rules($promotion), [
            'name.required' => 'Promotion Name is required.',
            'name.max' => 'Promotion Name must not exceed 120 characters.',
            'name.regex' => 'Promotion Name contains unsupported characters. Use valid letters, numbers, spaces, and punctuation.',
            'discount_type.required' => 'Discount Type is required.',
            'discount_type.in' => 'Select a valid discount type: Amount or Percentage.',
            'discount_value.required' => 'Discount Value is required.',
            'discount_value.gt' => 'Discount Value must be greater than zero.',
            'discount_value.max' => 'Discount Value cannot exceed the maximum allowed amount.',
            'discount_value.decimal' => 'Discount Value can have up to two decimal places.',
            'starts_at.date_format' => 'Enter a valid Promotion Start Date.',
            'ends_at.date_format' => 'Enter a valid Promotion End Date.',
            'ends_at.after_or_equal' => 'End Date must be on or after Start Date.',
        ]);
        $validator->after(function ($validator) use ($request, $promotion): void {
            if ($validator->errors()->has('discount_value') || $validator->errors()->has('discount_type')) return;
            $type = strtoupper((string) $request->input('discount_type'));
            $value = $request->input('discount_value');
            if (! is_numeric($value)) return;

            if ($type === 'PERCENT' && (float) $value > 100) {
                $validator->errors()->add('discount_value', 'Percentage discount cannot exceed 100%.');
            }

            $package = $promotion?->package;
            if (! $validator->errors()->has('discount_value') && $type === 'AMOUNT' && $package && (float) $value > (float) $package->price) {
                $validator->errors()->add('discount_value', "Fixed discount cannot exceed the assigned package's base price.");
            }
        });

        return $validator->validate();
    }

    private function ensureCanView(): void
    {
        abort_unless(auth()->user()?->isMainAdmin() || auth()->user()?->isBranchAdmin(), 403, 'Unauthorized');
    }

    private function ensureCanManage(): void
    {
        abort_unless(auth()->user()?->isMainAdmin(), 403, 'Only System Admin can manage promotions.');
    }
}
