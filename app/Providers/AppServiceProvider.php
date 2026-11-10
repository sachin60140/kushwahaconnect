<?php

namespace App\Providers;

use App\Services\Sms\LogSms;
use App\Services\Sms\SmartPingSms;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsSender;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Resolved per use so changes in Admin → SMS Settings apply immediately.
        $this->app->bind(SmsSender::class, function () {
            $config = SmsConfig::resolve();

            return $config['enabled'] ? new SmartPingSms($config) : new LogSms;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Vite::prefetch(concurrency: 3);

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $resetUrl = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject('Reset your KushwahaConnect password')
                ->greeting('Namaste '.$notifiable->name.',')
                ->line('We received a request to reset the password for your KushwahaConnect account.')
                ->action('Reset Password', $resetUrl)
                ->line('This secure link will expire in '.config('auth.passwords.'.config('auth.defaults.passwords').'.expire').' minutes.')
                ->line('If you did not request this, you can safely ignore this email. Your password will remain unchanged.')
                ->salutation('Regards, KushwahaConnect Team');
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('Verify your KushwahaConnect email')
                ->greeting('Namaste '.$notifiable->name.',')
                ->line('Please verify your email address to keep your KushwahaConnect account secure and active.')
                ->action('Verify Email Address', $url)
                ->line('If you did not create a KushwahaConnect account, no action is required.')
                ->salutation('Regards, KushwahaConnect Team');
        });
    }
}
