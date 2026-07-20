<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseDocument extends Model
{
    public const TYPE_FUNERAL_CONTRACT = 'funeral_contract';

    public $timestamps = false;

    protected $fillable = [
        'case_id',
        'document_type',
        'contract_number',
        'file_name',
        'file_path',
        'generated_by',
        'generated_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
    ];

    public function funeralCase()
    {
        return $this->belongsTo(FuneralCase::class, 'case_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function isFuneralContract(): bool
    {
        return $this->document_type === self::TYPE_FUNERAL_CONTRACT;
    }
}
