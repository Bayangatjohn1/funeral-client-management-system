<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CaseAttachment extends Model
{
    public const TYPE_TARPAULIN = 'tarpaulin';

    protected $fillable = [
        'case_id',
        'attachment_type',
        'file_name',
        'file_path',
        'uploaded_by',
    ];

    public function funeralCase()
    {
        return $this->belongsTo(FuneralCase::class, 'case_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function publicUrl(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
}
