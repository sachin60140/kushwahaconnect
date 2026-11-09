<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class HelpRequest extends Model
{
    /** Statuses whose public requests are listed and shareable. */
    public const PUBLIC_STATUSES = ['Approved', 'Assigned', 'In Progress', 'Resolved'];

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query->where('is_public', true)->whereIn('status', self::PUBLIC_STATUSES);
    }

    protected $casts = [
        'is_public' => 'boolean',
    ];
}
