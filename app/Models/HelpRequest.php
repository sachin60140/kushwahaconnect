<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HelpRequest extends Model
{
    /** Statuses whose public requests are listed and shareable. */
    public const PUBLIC_STATUSES = ['Approved', 'Assigned', 'In Progress', 'Resolved'];

    public const STATUSES = ['Pending', 'Approved', 'Assigned', 'In Progress', 'Resolved', 'Rejected'];

    public const URGENCIES = ['Normal', 'Urgent', 'Emergency'];

    public const CATEGORIES = [
        'Education Help',
        'Job Help',
        'Business Help',
        'Medical Help',
        'Emergency Help',
        'Government Scheme Help',
        'Legal Guidance',
        'Other',
    ];

    /** Max supporting documents / photos per request. */
    public const MAX_DOCUMENTS = 5;

    /** Max description length, shared by the public and admin forms. */
    public const MAX_DESCRIPTION = 10000;

    protected $guarded = ['id'];

    // Storage paths stay server-side; browsers get access-checked URLs.
    protected $hidden = ['upi_qr'];

    protected $appends = ['upi_qr_url'];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    protected static function booted(): void
    {
        // Remove stored files with the request (document rows cascade in the database).
        // Paths are collected before the rows go; files are deleted once committed.
        static::deleting(function (HelpRequest $help) {
            $paths = $help->documents()->pluck('path')->push($help->upi_qr)->filter()->values()->all();

            if ($paths) {
                DB::afterCommit(fn () => Storage::disk('local')->delete($paths));
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function documents()
    {
        return $this->hasMany(HelpRequestDocument::class)->oldest('id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_public', true)->whereIn('status', self::PUBLIC_STATUSES);
    }

    public function isPubliclyVisible(): bool
    {
        return $this->is_public && in_array($this->status, self::PUBLIC_STATUSES, true);
    }

    /** Documents, photos and the UPI QR: everyone while public & approved; admins always. */
    public function filesVisibleTo(?User $viewer): bool
    {
        return $this->isPubliclyVisible()
            || ($viewer !== null && in_array($viewer->role, ['admin', 'super_admin'], true));
    }

    public function hasPaymentDetails(): bool
    {
        return (bool) ($this->bank_account_number || $this->upi_id || $this->upi_qr);
    }

    public function getUpiQrUrlAttribute(): ?string
    {
        // Versioned by the stored file (a new random name per upload), so a replaced QR
        // gets a new URL and browsers never keep showing a cached old payee QR.
        return $this->upi_qr && $this->id
            ? route('help.upi-qr', ['id' => $this->id, 'v' => substr(sha1($this->upi_qr), 0, 10)])
            : null;
    }
}
