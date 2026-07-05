<?php

namespace App\Models;

use App\Support\PublicUpload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PublicRepresentative extends Model
{
    public const CATEGORIES = [
        'Lok Sabha MP',
        'Rajya Sabha MP',
        'Union Minister',
        'Chief Minister',
        'State Minister',
        'MLA',
        'MLC',
        'Mayor',
        'Zila Parishad',
        'Municipal Representative',
        'Panchayat Representative',
        'Former Representative',
    ];

    public const STATUSES = ['current', 'former'];

    protected $fillable = [
        'name',
        'photo_path',
        'representative_status',
        'category',
        'current_designation',
        'former_designations',
        'political_party',
        'constituency',
        'state',
        'district',
        'house_or_body',
        'term_start_date',
        'term_end_date',
        'biography_hi',
        'biography_en',
        'official_website',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'youtube_url',
        'x_url',
        'government_source_url',
        'election_source_url',
        'last_verified_at',
        'verification_note',
        'is_verified',
        'is_featured',
        'display_order',
        'is_published',
        'archived_at',
    ];

    protected $casts = [
        'term_start_date' => 'date',
        'term_end_date' => 'date',
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
        return $this->representative_status === 'current'
            ? 'Current Representative'
            : 'Former Representative';
    }
}
