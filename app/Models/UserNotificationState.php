<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationState extends Model
{
    protected $guarded = [];

    protected $casts = [
        'read_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];
}
