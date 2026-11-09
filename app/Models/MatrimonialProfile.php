<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MatrimonialProfile extends Model
{
    /** Biodata upload rules (PDF or image, private local disk). */
    public const BIODATA_RULES = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

    public const BIODATA_DIRECTORY = 'matrimonial_biodata';

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

    /**
     * Validation rules for a complete matrimonial profile, shared by the public
     * registration form and the member's own profile page. Key details are required.
     *
     * @return array<string, mixed>
     */
    public static function detailRules(): array
    {
        $text = ['nullable', 'string', 'max:255'];

        return [
            // Basics
            'profile_for' => ['required', 'string', 'max:50'],
            'full_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'dob' => ['required', 'date', 'before:today'],
            'marital_status' => ['required', 'string', 'max:50'],
            'height' => ['required', 'string', 'max:50'],
            'manglik' => $text,
            'gotra' => $text,
            'mother_tongue' => $text,
            'current_city' => $text,
            'native_place' => $text,
            // Education & career
            'education' => ['required', 'string', 'max:255'],
            'college' => $text,
            'occupation' => ['required', 'string', 'max:255'],
            'company' => $text,
            'annual_income' => $text,
            'work_location' => $text,
            // Family
            'father_name' => ['required', 'string', 'max:255'],
            'father_occupation' => $text,
            'mother_name' => $text,
            'mother_occupation' => $text,
            'brothers' => ['nullable', 'integer', 'min:0', 'max:20'],
            'sisters' => ['nullable', 'integer', 'min:0', 'max:20'],
            'family_type' => $text,
            'family_location' => ['required', 'string', 'max:255'],
            // Partner preferences
            'pref_age_range' => $text,
            'pref_height' => $text,
            'pref_education' => $text,
            'pref_location' => $text,
            'pref_profession' => $text,
            'other_expectations' => ['nullable', 'string', 'max:2000'],
            // Contact
            'mobile' => ['required', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
        ];
    }

    public function getPhotoUrlAttribute()
    {
        // Respect a value set explicitly (e.g. null when privacy settings hide the photo).
        if (array_key_exists('photo_url', $this->attributes)) {
            return $this->attributes['photo_url'];
        }

        return $this->photo ? route('matrimonial.photo', $this->id) : null;
    }

    /** Store a new biodata file, replacing (and deleting) any previous one. */
    public function replaceBiodata(UploadedFile $file): void
    {
        $previous = $this->biodata;
        $this->biodata = $file->store(self::BIODATA_DIRECTORY, 'local');
        $this->save();

        if ($previous && $previous !== $this->biodata) {
            Storage::disk('local')->delete($previous);
        }
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id !== null && (int) $this->user_id === (int) $user->id;
    }

    public function hasAcceptedConnectionWith(?User $viewer): bool
    {
        if (! $viewer || ! $this->user_id) {
            return false;
        }

        $owner = (int) $this->user_id;
        $viewerId = (int) $viewer->id;

        return Connection::where('status', 'accepted')
            ->where(function ($query) use ($owner, $viewerId) {
                $query->where(fn ($q) => $q->where('sender_id', $viewerId)->where('receiver_id', $owner))
                    ->orWhere(fn ($q) => $q->where('sender_id', $owner)->where('receiver_id', $viewerId));
            })
            ->exists();
    }

    /**
     * Biodata is visible to the owner and admins, and to other members only after an
     * accepted interest (connection) with the owner, on an approved profile.
     */
    public function biodataVisibleTo(?User $viewer): bool
    {
        if (! $this->biodata || ! $viewer) {
            return false;
        }

        if ($this->isOwnedBy($viewer) || in_array($viewer->role, ['admin', 'super_admin'], true)) {
            return true;
        }

        return $this->status === 'approved' && $this->hasAcceptedConnectionWith($viewer);
    }
}
