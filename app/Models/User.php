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
        'otp_code',
        'otp_expires_at',
        'mobile_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'otp_code',
    ];

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
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'roles' => 'array',
        ];
    }
}
