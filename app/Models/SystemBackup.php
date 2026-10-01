<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemBackup extends Model
{
    protected $fillable = ['created_by', 'type', 'status', 'disk', 'path', 'size_bytes', 'checksum', 'verified_at', 'expires_at', 'failure_message', 'metadata'];

    protected $casts = ['verified_at' => 'datetime', 'expires_at' => 'datetime', 'metadata' => 'array'];

    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
    public function restoreRequests() { return $this->hasMany(RestoreRequest::class); }
}
