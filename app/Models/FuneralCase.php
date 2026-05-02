<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use App\Support\BranchScoped;

class FuneralCase extends Model
{
    use BranchScoped, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'client_id',
        'deceased_id',
        'package_id',
        'case_number',  // sequential INT per branch (Phase 1+)
        'case_code',
        'service_type',
        'service_requested_at',
        'service_package',
        'coffin_type',
        'custom_package_name',
        'custom_package_price',
        'custom_package_inclusions',
        'custom_package_freebies',
        'wake_location',
        'funeral_service_at',
        'interment_at',
        'transport_option',
        'transport_notes',
        'coffin_length_cm',
        'coffin_size',
        'embalming_required',
        'embalming_status',
        'embalming_at',
        'embalming_notes',
        'additional_services',
        'additional_service_amount',
        'subtotal_amount',
        'discount_type',
        'discount_value_type',
        'discount_value',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'discount_note',
        'total_amount',
        'total_paid',
        'balance_amount',
        'payment_status',
        'initial_payment_type',
        'paid_at',
        'case_status',
        'reported_branch_id',
        'reporter_name',
        'reporter_contact',
        'reported_at',
        'encoded_by',
        'entry_source',
        'verification_status',
        'verified_by',
        'verified_at',
        'verification_note',
    ];

    protected $casts = [
        'service_requested_at' => 'date',
        'funeral_service_at' => 'date',
        'interment_at' => 'datetime',
        'coffin_length_cm' => 'decimal:2',
        'embalming_required' => 'boolean',
        'embalming_at' => 'datetime',
        'deleted_at' => 'datetime',
        'additional_service_amount' => 'decimal:2',
        'custom_package_price' => 'decimal:2',
        'subtotal_amount' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_paid' => 'decimal:2',
        'balance_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'reported_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Sync case_status changes to service_details (Issue 4).
        // Mapping: DRAFT→pending, ACTIVE→ongoing, COMPLETED→completed.
        // service_details uses lowercase spec values; funeral_cases uses UPPERCASE legacy values.
        static::saved(function (self $case) {
            if ($case->wasChanged('case_status')) {
                $map = ['DRAFT' => 'pending', 'ACTIVE' => 'ongoing', 'COMPLETED' => 'completed'];
                $mapped = $map[$case->case_status] ?? null;
                if ($mapped) {
                    $case->serviceDetail()->update(['case_status' => $mapped]);
                }
            }

            // Sync interment_at to service_details.internment_date (Issue 5).
            // service_details is the authoritative future store; keep it current on every save.
            if ($case->wasChanged('interment_at')) {
                $case->serviceDetail()->update([
                    'internment_date' => $case->interment_at
                        ? $case->interment_at->toDateString()
                        : null,
                ]);
            }
        });
    }

    public function client()
    {
        return $this->belongsTo(\App\Models\Client::class);
    }

    public function branch()
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function deceased()
    {
        return $this->belongsTo(\App\Models\Deceased::class);
    }

    public function package()
    {
        return $this->belongsTo(\App\Models\Package::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }

    public function encodedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'encoded_by');
    }

    public function reportedBranch()
    {
        return $this->belongsTo(\App\Models\Branch::class, 'reported_branch_id');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    public function caseNotes()
    {
        return $this->hasMany(\App\Models\CaseNote::class);
    }

    /**
     * Normalized service schedule (Phase 1+).
     * 1:1 — one service_details row per funeral case.
     */
    public function serviceDetail()
    {
        return $this->hasOne(\App\Models\ServiceDetail::class);
    }

    /**
     * Returns the next available case_number for a given branch.
     * Call this before creating a new FuneralCase to assign the number atomically.
     *
     * NOTE: Uses MAX+1, which is not race-safe on its own. The UNIQUE(branch_id, case_number)
     * constraint acts as the hard guard. Controllers wrap FuneralCase::create() in a retry
     * loop that catches error 1062 and calls this again with a fresh MAX.
     */
    public static function nextCaseNumber(int $branchId): int
    {
        $max = static::where('branch_id', $branchId)->max('case_number');
        return ($max ?? 0) + 1;
    }

    /**
     * Complete active cases whose recorded interment datetime has passed.
     */
    public static function completePastInterments(?CarbonInterface $now = null): int
    {
        $now = $now ? Carbon::instance($now) : now();
        $hasCompletedAt = Schema::hasColumn((new static())->getTable(), 'completed_at');
        $completed = 0;

        static::query()
            ->where('case_status', 'ACTIVE')
            ->whereNotNull('interment_at')
            ->where('interment_at', '<=', $now)
            ->with('serviceDetail')
            ->chunkById(100, function ($cases) use ($now, $hasCompletedAt, &$completed) {
                foreach ($cases as $case) {
                    $attributes = ['case_status' => 'COMPLETED'];

                    if ($hasCompletedAt) {
                        $attributes['completed_at'] = $now;
                    }

                    $case->forceFill($attributes)->save();
                    $completed++;
                }
            });

        return $completed;
    }

    /**
     * Re-derives total_paid / balance_amount / payment_status by summing all
     * non-voided payment rows for this case.
     *
     * Call this inside the same DB transaction immediately after any payment mutation
     * (create or void) so the aggregate columns stay authoritative. Handles legacy
     * payments with NULL status (treated as valid).
     */
    public function recalculatePaymentTotals(): void
    {
        $totalPaid = round(
            (float) $this->payments()
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'VOID');
                })
                ->sum('amount'),
            2
        );
        $totalAmount = round((float) $this->total_amount, 2);
        $balance     = round(max($totalAmount - $totalPaid, 0), 2);
        $status      = $totalPaid <= 0 ? 'UNPAID' : ($balance > 0 ? 'PARTIAL' : 'PAID');

        $this->update([
            'total_paid'     => $totalPaid,
            'balance_amount' => $balance,
            'payment_status' => $status,
        ]);
    }
}
