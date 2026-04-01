<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\BranchScoped;

class FuneralCase extends Model
{
    use BranchScoped;

    protected $fillable = [
        'branch_id',
        'client_id',
        'deceased_id',
        'package_id',
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
}
