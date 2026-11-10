<?php

namespace App\Services\Sms;

/**
 * In-memory driver for tests: records messages instead of sending them.
 */
class FakeSms implements SmsSender
{
    /** @var array<int, array{mobile: string, message: string}> */
    public array $sent = [];

    public bool $shouldFail = false;

    public function send(string $mobile, string $message): void
    {
        if ($this->shouldFail) {
            throw new SmsException('Fake SMS failure.');
        }

        $this->sent[] = ['mobile' => $mobile, 'message' => $message];
    }

    public function lastMessageTo(string $mobile): ?string
    {
        foreach (array_reverse($this->sent) as $sms) {
            if ($sms['mobile'] === $mobile) {
                return $sms['message'];
            }
        }

        return null;
    }

    public function lastCodeTo(string $mobile): ?string
    {
        $message = $this->lastMessageTo($mobile);

        return $message && preg_match('/\b(\d{6})\b/', $message, $m) ? $m[1] : null;
    }
}
