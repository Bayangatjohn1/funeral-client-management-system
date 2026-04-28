<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CaseNote extends Model
{
    protected $fillable = [
        'funeral_case_id',
        'author_id',
        'branch_id',
        'note',
        'visibility',   // staff | admin | owner
    ];

    public function funeralCase()
    {
        return $this->belongsTo(FuneralCase::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
