<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseDocumentRevision extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'case_document_id', 'revision_number', 'contract_snapshot', 'file_name', 'file_path',
        'generated_by', 'generated_at', 'superseded_by', 'superseded_at', 'reason',
    ];

    protected $casts = [
        'contract_snapshot' => 'array',
        'generated_at' => 'datetime',
        'superseded_at' => 'datetime',
    ];
}
