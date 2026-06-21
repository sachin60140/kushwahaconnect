<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsEvent extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'event_date' => 'datetime',
        'is_published' => 'boolean',
    ];
}
