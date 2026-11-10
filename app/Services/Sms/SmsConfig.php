<?php

namespace App\Services\Sms;

use App\Models\SmsSetting;
use Throwable;

/**
 * Effective SMS settings: values saved by a super admin win, .env / config fills the gaps.
 */
class SmsConfig
{
    private const FIELDS = [
        'username',
        'password',
        'sender_id',
        'dlt_content_id',
        'dlt_principal_entity_id',
        'base_url',
        'otp_message',
    ];

    /**
     * @return array{enabled: bool, username: ?string, password: ?string, sender_id: ?string, dlt_content_id: ?string, dlt_principal_entity_id: ?string, base_url: ?string, otp_message: ?string, timeout: int, from_database: bool}
     */
    public static function resolve(): array
    {
        $config = (array) config('services.smartping', []);
        $saved = SmsSetting::current();

        $resolved = [];
        foreach (self::FIELDS as $field) {
            $value = null;
            if ($saved) {
                try {
                    $value = $saved->{$field};
                } catch (Throwable) {
                    $value = null; // e.g. password encrypted with a different APP_KEY
                }
            }
            $resolved[$field] = filled($value) ? (string) $value : ($config[$field] ?? null);
        }

        // A saved settings row decides on/off; without one the SMS_DRIVER env value does.
        $resolved['enabled'] = $saved ? (bool) $saved->enabled : config('services.sms.driver') === 'smartping';
        $resolved['timeout'] = (int) ($config['timeout'] ?? 15);
        $resolved['from_database'] = $saved !== null;

        return $resolved;
    }

    /**
     * Real SMS can go out: enabled and the account credentials are present.
     */
    public static function configured(?array $resolved = null): bool
    {
        $resolved ??= self::resolve();

        return $resolved['enabled'] && filled($resolved['username']) && filled($resolved['password']);
    }
}
