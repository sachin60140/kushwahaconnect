<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Sent once per "SMS degraded" episode to super admins (see SmsHealth).
 * Contains no credentials, numbers or codes.
 */
class SmsDegradedAlert extends Mailable
{
    public function __construct(public string $reason, public string $since)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'KushwahaConnect: SMS / OTP सेवा रुकी है (SMS service degraded)');
    }

    public function content(): Content
    {
        [$en, $hi] = match ($this->reason) {
            '6001' => ['SmartPing balance is finished. Please recharge.', 'SmartPing बैलेंस खत्म — रिचार्ज करें।'],
            '2070' => ['SmartPing rejected the user ID / password. Please update them in SMS Settings.', 'SmartPing ने यूज़र ID / पासवर्ड अस्वीकार किया — SMS सेटिंग्स में अपडेट करें।'],
            '2051' => ['The sender ID is not registered with SmartPing.', 'Sender ID SmartPing पर रजिस्टर्ड नहीं है।'],
            '7001' => ['The DLT template (content) ID was not accepted.', 'DLT टेम्पलेट (content) ID स्वीकार नहीं हुआ।'],
            default => ['SmartPing could not be reached several times in a row.', 'SmartPing से बार-बार कनेक्शन नहीं हो पाया।'],
        };

        $url = e(route('admin.sms-settings.show'));
        $since = e($this->since);
        $code = e($this->reason);

        return new Content(htmlString: <<<HTML
            <p><strong>{$hi}</strong></p>
            <p>{$en}</p>
            <p>Since: {$since} (code: {$code})</p>
            <p>OTP verification on forms is paused for 30 minutes so members are not blocked. It resumes automatically after a successful SMS, or use "Retry SMS now" in SMS Settings.</p>
            <p><a href="{$url}">{$url}</a></p>
            HTML);
    }
}
