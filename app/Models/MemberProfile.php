<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MemberProfile extends Model
{
    protected $guarded = ['id'];

    protected $attributes = [
        'status' => 'approved',
    ];

    protected $casts = [
        'volunteer_interest' => 'boolean',
        'join_district_team' => 'boolean',
        'is_public' => 'boolean',
        'show_mobile' => 'boolean',
        'allow_whatsapp' => 'boolean',
        'consent' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    /**
     * Profiles that may be listed publicly: approved, marked public, and not
     * hidden by the owner's "Make my Community Profile public" privacy setting.
     */
    public function scopeVisibleInDirectory(Builder $query): Builder
    {
        return $query->where('status', 'approved')
            ->where('is_public', true)
            ->whereDoesntHave('user.privacySetting', function (Builder $settings) {
                $settings->where('public_profile_visible', false);
            });
    }

    /** Optional details the short Join form skips, in the order the dashboard lists them. */
    public const CHECKLIST_FIELDS = [
        'profile_photo',
        'father_husband_name',
        'dob',
        'whatsapp',
        'city_village',
        'current_address',
        'pin_code',
        'email',
    ];

    /**
     * Which optional profile details are still empty for $user.
     *
     * @return array{missing: list<string>, percent: int}
     */
    public static function checklistFor(User $user): array
    {
        $profile = self::where('user_id', $user->id)->first();
        $total = count(self::CHECKLIST_FIELDS);

        $missing = array_values(array_filter(
            self::CHECKLIST_FIELDS,
            fn (string $field) => ! $profile || blank($profile->{$field}),
        ));

        return [
            'missing' => $missing,
            'percent' => (int) round(($total - count($missing)) / $total * 100),
        ];
    }

    /**
     * Whether the profile photo may be shown to the given viewer, honouring the
     * owner's photo_visibility privacy setting. Expects user.privacySetting loaded.
     *
     * @param  array<int>  $connectedUserIds  user ids the viewer has an accepted connection with
     */
    public function photoVisibleTo(?User $viewer, array $connectedUserIds = []): bool
    {
        if (! $this->profile_photo) {
            return false;
        }

        $ownerId = $this->user_id !== null ? (int) $this->user_id : null;

        if ($viewer && $ownerId !== null && (int) $viewer->id === $ownerId) {
            return true;
        }

        $visibility = $this->user?->privacySetting?->photo_visibility ?? 'public';

        return match ($visibility) {
            'public' => true,
            'connections_only' => $viewer !== null && $ownerId !== null && in_array($ownerId, $connectedUserIds, true),
            default => false,
        };
    }
}
