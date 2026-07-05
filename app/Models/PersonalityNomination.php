<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PersonalityNomination extends Model
{
    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'more_information_required' => 'More Information Required',
        'verified' => 'Verified',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'published' => 'Published',
    ];

    protected $fillable = [
        'person_name',
        'category',
        'profession_or_designation',
        'state',
        'district',
        'achievement',
        'biography',
        'photograph_path',
        'supporting_document_path',
        'source_links',
        'social_links',
        'nominator_name',
        'nominator_mobile',
        'relationship',
        'consent',
        'status',
        'admin_note',
    ];

    protected $casts = [
        'consent' => 'boolean',
    ];

    protected $appends = ['photograph_url', 'supporting_document_url'];

    public function getPhotographUrlAttribute(): ?string
    {
        return $this->photograph_path ? Storage::disk('public')->url($this->photograph_path) : null;
    }

    public function getSupportingDocumentUrlAttribute(): ?string
    {
        return $this->supporting_document_path ? Storage::disk('public')->url($this->supporting_document_path) : null;
    }
}
