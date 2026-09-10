<?php

namespace App\Models;

use App\Support\BranchScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class IntakeDraft extends Model
{
    use BranchScoped, SoftDeletes;

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_SUBMITTED = 'SUBMITTED';
    public const STATUS_DISCARDED = 'DISCARDED';

    protected $fillable = [
        'draft_number',
        'branch_id',
        'created_by',
        'payload',
        'current_step',
        'entry_mode',
        'status',
        'submitted_case_id',
        'last_saved_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'current_step' => 'integer',
        'last_saved_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $draft) {
            if (! $draft->draft_number) {
                $draft->draft_number = static::nextDraftNumber();
            }
        });
    }

    public static function nextDraftNumber(): string
    {
        return 'DRF-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6));
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedCase()
    {
        return $this->belongsTo(FuneralCase::class, 'submitted_case_id');
    }
}
