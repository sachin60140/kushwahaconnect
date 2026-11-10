<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * Single-row SMS gateway settings (Super admin → SMS Settings).
 * The password is encrypted at rest and never serialized.
 */
class SmsSetting extends Model
{
    protected $fillable = [
        'enabled',
        'username',
        'password',
        'sender_id',
        'dlt_content_id',
        'dlt_principal_entity_id',
        'base_url',
        'otp_message',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'password' => 'encrypted',
        ];
    }

    /**
     * The saved settings row, or null when none exists or the table is unavailable
     * (e.g. before migrations have run).
     */
    public static function current(): ?self
    {
        try {
            return static::query()->first();
        } catch (Throwable) {
            return null;
        }
    }
}
