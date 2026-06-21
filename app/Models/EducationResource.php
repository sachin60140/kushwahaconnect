<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EducationResource extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'deadline_date' => 'date',
        'is_published' => 'boolean',
    ];
}
