<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class MatrimonialProfile extends Model
{
    /** Biodata upload rules (PDF or image, private local disk). */
    public const BIODATA_RULES = ['file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];

    public const BIODATA_DIRECTORY = 'matrimonial_biodata';

    /** Stored profile_for value when people register themselves (MAT_PROFILE_FOR in optionLabels.js). */
    public const PROFILE_FOR_SELF = 'Self';

    /** A new profile that duplicates one in these statuses is refused; rejected ones may re-apply. */
    public const ACTIVE_STATUSES = ['pending', 'approved'];

    /** Name as compared for duplicates: lower case, whitespace collapsed and trimmed. */
    public static function comparableName(?string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $name)));
    }

    public static function isSelf(?string $profileFor): bool
    {
        return strcasecmp(trim((string) $profileFor), self::PROFILE_FOR_SELF) === 0;
    }

    /**
     * Would a new profile duplicate a live (pending or approved) one for the same person?
     * Same person = same mobile and same name, or a second "Self" profile on the same
     * mobile. The same mobile with a different name is allowed (a parent registering two
     * children). Returns the dictionary key of the error, or null when allowed.
     *
     * $mobile must already be normalised to bare 10 digits.
     */
    public static function duplicateErrorFor(string $mobile, string $fullName, ?string $profileFor): ?string
    {
        $live = static::query()
            ->where('mobile', $mobile)
            ->whereIn('status', self::ACTIVE_STATUSES)
            ->get(['id', 'full_name', 'profile_for']);

        $name = self::comparableName($fullName);

        if ($live->contains(fn (self $p) => self::comparableName($p->full_name) === $name)) {
            return 'mat_error_duplicate_profile';
        }

        if (self::isSelf($profileFor) && $live->contains(fn (self $p) => self::isSelf($p->profile_for))) {
            return 'mat_error_duplicate_self';
        }

        return null;
    }

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

    /**
     * SQL for a name compared case-insensitively with spaces trimmed and runs of spaces
     * collapsed (three REPLACE passes cover runs of up to 8). SQLite and MySQL alike.
     */
    private static function nameKeySql(string $column): string
    {
        return "LOWER(TRIM(REPLACE(REPLACE(REPLACE({$column}, '  ', ' '), '  ', ' '), '  ', ' ')))";
    }

    /** PHP twin of nameKeySql(), to look up the grouped rows (ASCII lower case, like SQLite). */
    private static function nameKey(?string $name): string
    {
        return strtolower(trim(preg_replace('/ {2,}/', ' ', (string) $name), ' '));
    }

    /**
     * Admin "Duplicates only" filter: profiles that share mobile + name (case and spacing ignored) with
     * another profile, or "Self" profiles sharing a mobile with another "Self" profile.
     * Correlated EXISTS subqueries: portable between SQLite (tests) and MySQL.
     */
    public function scopeOnlyDuplicates(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereExists(function ($sub) {
                $sub->selectRaw('1')->from('matrimonial_profiles as d')
                    ->whereColumn('d.mobile', 'matrimonial_profiles.mobile')
                    ->whereColumn('d.id', '!=', 'matrimonial_profiles.id')
                    ->whereRaw(self::nameKeySql('d.full_name').' = '.self::nameKeySql('matrimonial_profiles.full_name'));
            })->orWhere(function (Builder $self) {
                $self->whereRaw('LOWER(TRIM(matrimonial_profiles.profile_for)) = ?', ['self'])
                    ->whereExists(function ($sub) {
                        $sub->selectRaw('1')->from('matrimonial_profiles as d')
                            ->whereColumn('d.mobile', 'matrimonial_profiles.mobile')
                            ->whereColumn('d.id', '!=', 'matrimonial_profiles.id')
                            ->whereRaw('LOWER(TRIM(d.profile_for)) = ?', ['self']);
                    });
            });
        });
    }

    /**
     * Size of each listed profile's duplicate group (all statuses), keyed by id; profiles
     * without a duplicate are left out. Two grouped queries limited to the listed mobiles.
     *
     * @param  iterable<self>  $profiles
     * @return array<int, int>
     */
    public static function duplicateCounts(iterable $profiles): array
    {
        $profiles = collect($profiles);
        $mobiles = $profiles->pluck('mobile')->filter()->unique()->values()->all();

        if ($mobiles === []) {
            return [];
        }

        $byName = static::query()
            ->whereIn('mobile', $mobiles)
            ->selectRaw('mobile, '.self::nameKeySql('full_name').' as name_key, COUNT(*) as total')
            ->groupByRaw('mobile, '.self::nameKeySql('full_name'))
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->mobile."\0".$row->name_key => (int) $row->total]);

        $selfByMobile = static::query()
            ->whereIn('mobile', $mobiles)
            ->whereRaw('LOWER(TRIM(profile_for)) = ?', ['self'])
            ->selectRaw('mobile, COUNT(*) as total')
            ->groupBy('mobile')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('total', 'mobile');

        $counts = [];

        foreach ($profiles as $profile) {
            $count = max(
                $byName[$profile->mobile."\0".self::nameKey($profile->full_name)] ?? 0,
                self::isSelf($profile->profile_for) ? (int) ($selfByMobile[$profile->mobile] ?? 0) : 0,
            );

            if ($count > 1) {
                $counts[$profile->id] = $count;
            }
        }

        return $counts;
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
