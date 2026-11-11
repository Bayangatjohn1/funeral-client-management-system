<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HistoricalCaseSubmissionReview extends Model
{
    public $timestamps = false;
    protected $fillable = ['historical_case_submission_id', 'reviewed_by', 'action', 'note', 'created_at'];
    protected $casts = ['created_at' => 'datetime'];
    public function reviewer() { return $this->belongsTo(User::class, 'reviewed_by'); }
}
