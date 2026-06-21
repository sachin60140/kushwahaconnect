<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shortlist extends Model
{
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function matrimonialProfile()
    {
        return $this->belongsTo(MatrimonialProfile::class);
    }
}
