<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
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
        static::deleting(function (HelpRequest $help) {
            $disk = Storage::disk('local');

            foreach ($help->documents()->pluck('path') as $path) {
                $disk->delete($path);
            }

            if ($help->upi_qr) {
                $disk->delete($help->upi_qr);
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
        return $this->upi_qr && $this->id ? route('help.upi-qr', $this->id) : null;
    }
}
