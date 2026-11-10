<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'mobile',
        'password',
        'role',
        'roles',
        'status',
        'language_preference',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected static function booted(): void
    {
        // A changed number has not been verified yet.
        static::saving(function (User $user) {
            if ($user->exists && $user->isDirty('mobile') && ! $user->isDirty('mobile_verified_at')) {
                $user->mobile_verified_at = null;
            }
        });
    }

    public function shortlists()
    {
        return $this->hasMany(Shortlist::class);
    }

    public function jobPostings()
    {
        return $this->hasMany(JobPosting::class);
    }

    public function businessListings()
    {
        return $this->hasMany(BusinessListing::class);
    }

    public function helpRequests()
    {
        return $this->hasMany(HelpRequest::class);
    }

    public function volunteer()
    {
        return $this->hasOne(Volunteer::class);
    }

    public function privacySetting()
    {
        return $this->hasOne(UserPrivacySetting::class);
    }

    public function memberProfile()
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function loginHistories()
    {
        return $this->hasMany(LoginHistory::class);
    }

    // Connections where this user sent the request
    public function sentConnections()
    {
        return $this->hasMany(Connection::class, 'sender_id');
    }

    // Connections where this user received the request
    public function receivedConnections()
    {
        return $this->hasMany(Connection::class, 'receiver_id');
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
        ];
    }
}
