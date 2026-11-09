<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatrimonialProfile extends Model
{
    protected $guarded = ['id'];
    protected $appends = ['photo_url'];

    protected $casts = [
        'is_public' => 'boolean',
        'hide_photo' => 'boolean',
        'consent' => 'boolean',
        'is_verified' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getPhotoUrlAttribute()
    {
        // Respect a value set explicitly (e.g. null when privacy settings hide the photo).
        if (array_key_exists('photo_url', $this->attributes)) {
            return $this->attributes['photo_url'];
        }

        return $this->photo ? route('matrimonial.photo', $this->id) : null;
    }
}
