<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalCaseSubmission extends Model
{
    public const PENDING = 'PENDING_APPROVAL';
    public const NEEDS_CHANGES = 'NEEDS_CHANGES';
    public const REJECTED = 'REJECTED';
    public const APPROVED = 'APPROVED';

    protected $fillable = [
        'submission_code', 'branch_id', 'encoded_by', 'authorized_by', 'official_case_id',
        'status', 'submission_data', 'review_note', 'authorized_at', 'resubmitted_at', 'parent_submission_id',
    ];

    protected $casts = [
        'submission_data' => 'array',
        'authorized_at' => 'datetime',
        'resubmitted_at' => 'datetime',
    ];

    public function branch() { return $this->belongsTo(Branch::class); }
    public function encoder() { return $this->belongsTo(User::class, 'encoded_by'); }
    public function authorizer() { return $this->belongsTo(User::class, 'authorized_by'); }
    public function officialCase() { return $this->belongsTo(FuneralCase::class, 'official_case_id'); }
    public function parentSubmission() { return $this->belongsTo(self::class, 'parent_submission_id'); }
    public function reviews() { return $this->hasMany(HistoricalCaseSubmissionReview::class); }
}
