<?php

namespace App\Models;

use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KushwahaPrideProfile extends Model
{
    public const CATEGORIES = [
        'Education',
        'Civil Services',
        'Business and Industry',
        'Medical and Healthcare',
        'Science and Technology',
        'Sports',
        'Arts and Culture',
        'Social Service',
        'Law and Judiciary',
        'Agriculture and Innovation',
        'Defence and Security',
        'Media and Literature',
    ];

    protected $fillable = [
        'name_hi',
        'name_en',
        'photo_path',
        'category',
        'designation_hi',
        'designation_en',
        'state',
        'district',
        'city',
        'biography_hi',
        'biography_en',
        'achievements_hi',
        'achievements_en',
        'organisation',
        'official_website',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'youtube_url',
        'x_url',
        'verification_sources',
        'last_verified_at',
        'is_verified',
        'is_featured',
        'display_order',
        'is_published',
        'archived_at',
    ];

    protected $casts = [
        'last_verified_at' => 'date',
        'is_verified' => 'boolean',
        'is_featured' => 'boolean',
        'is_published' => 'boolean',
        'archived_at' => 'datetime',
    ];

    protected $appends = ['photo_url', 'neutral_label'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->whereNull('archived_at');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return PublicUpload::url($this->photo_path);
    }

    public function getNeutralLabelAttribute(): string
    {
        return $this->is_verified ? 'Verified Community Personality' : 'Inspiring Personality';
    }
}
