<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatrimonialProfile extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['photo_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute()
    {
        return $this->photo ? route('matrimonial.photo', $this->id) : null;
    }
}
