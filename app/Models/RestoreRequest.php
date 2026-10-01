<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RestoreRequest extends Model
{
    protected $fillable = ['system_backup_id', 'requested_by', 'status', 'reason', 'validated_at', 'executed_at', 'failure_message', 'validation_results'];
    protected $casts = ['validated_at' => 'datetime', 'executed_at' => 'datetime', 'validation_results' => 'array'];
    public function backup() { return $this->belongsTo(SystemBackup::class, 'system_backup_id'); }
    public function requester() { return $this->belongsTo(User::class, 'requested_by'); }
}
