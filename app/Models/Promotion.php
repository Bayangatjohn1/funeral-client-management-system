<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $fillable = [
        'package_id', 'name', 'discount_type', 'discount_value', 'starts_at', 'ends_at',
        'is_enabled', 'manually_stopped_at', 'manually_stopped_by', 'created_by', 'updated_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2', 'starts_at' => 'date', 'ends_at' => 'date',
        'is_enabled' => 'boolean', 'manually_stopped_at' => 'datetime',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function stoppedBy()
    {
        return $this->belongsTo(User::class, 'manually_stopped_by');
    }

    public function reviewFields(?Package $package = null): array
    {
        $issues = [];
        if (blank($this->name)) {
            $issues['name'] = 'Promotion Name is required.';
        } elseif (mb_strlen($this->name) > 120) {
            $issues['name'] = 'Promotion Name must not exceed 120 characters.';
        } elseif (! preg_match("~^[\\pL\\pM\\pN][\\pL\\pM\\pN\\s.,'()/&-]*$~u", trim($this->name))) {
            $issues['name'] = 'Promotion Name contains unsupported characters. Use valid letters, numbers, spaces, and punctuation.';
        }
        if (! in_array(strtoupper((string) $this->discount_type), ['AMOUNT', 'PERCENT'], true)) {
            $issues['discount_type'] = 'Select a valid discount type: Amount or Percentage.';
        }
        if (! is_numeric($this->discount_value) || (float) $this->discount_value <= 0) {
            $issues['discount_value'] = 'Discount Value must be greater than zero.';
        } elseif ((float) $this->discount_value > 9999999999.99) {
            $issues['discount_value'] = 'Discount Value cannot exceed the maximum allowed amount.';
        } elseif (preg_match('/\.\d{3,}$/', (string) $this->discount_value)) {
            $issues['discount_value'] = 'Discount Value can have up to two decimal places.';
        } elseif (strtoupper((string) $this->discount_type) === 'PERCENT' && (float) $this->discount_value > 100) {
            $issues['discount_value'] = 'Percentage discount cannot exceed 100%.';
        } elseif (strtoupper((string) $this->discount_type) === 'AMOUNT' && $package && (float) $this->discount_value > (float) $package->price) {
            $issues['discount_value'] = "Fixed discount cannot exceed the assigned package's base price.";
        }
        if ($this->starts_at && $this->ends_at && $this->ends_at->lt($this->starts_at)) {
            $issues['ends_at'] = 'End Date must be on or after Start Date.';
        }
        // A disabled legacy flag alone does not prove a System Admin manually
        // stopped this promotion. The explicit event timestamp is the evidence.
        if (! $this->is_enabled && ! $this->manually_stopped_at) {
            $issues['is_enabled'] = 'The promotion enabled state has no recorded manual action. Review before use.';
        }
        return $issues;
    }

    public function statusAt(?CarbonInterface $at = null, ?Package $package = null): string
    {
        // All supported create/edit paths validate required configuration.
        // Therefore an invalid persisted record here is legacy data retained
        // from before those rules, not a validation result for the current form.
        if ($this->exists && $this->reviewFields($package) !== []) return 'Incomplete Setup';
        $now = ($at ?? now('Asia/Manila'))->copy()->timezone('Asia/Manila');
        if ($this->ends_at && $now->gt($this->ends_at->copy()->endOfDay())) return 'Expired';
        if ($this->manually_stopped_at) return 'Turned Off';
        if ($this->starts_at && $now->lt($this->starts_at->copy()->startOfDay())) return 'Scheduled';
        return 'Active';
    }

    public function isEffectiveAt(?CarbonInterface $at = null, ?Package $package = null): bool
    {
        return $this->reviewFields($package) === [] && $this->statusAt($at, $package) === 'Active';
    }
}
