<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\GeneralAlertNotification;
use App\Notifications\ProfileApprovedNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailNotificationDesignTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_reset_email_uses_kushwahaconnect_branding(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $mail = (new ResetPassword('test-token'))->toMail($user);
        $html = (string) $mail->render();

        $this->assertSame('Reset your KushwahaConnect password', $mail->subject);
        $this->assertStringContainsString('Digital Community Platform for Kushwaha Samaj', $html);
        $this->assertStringContainsString('Reset Password', $html);
        $this->assertStringContainsString('KushwahaConnect Team', $html);
    }

    public function test_app_notifications_send_database_and_email_channels(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $general = new GeneralAlertNotification('Community Update', 'A new update is available.', '/dashboard');
        $approved = new ProfileApprovedNotification();

        $this->assertSame(['database', 'mail'], $general->via($user));
        $this->assertSame(['database', 'mail'], $approved->via($user));
        $this->assertSame('Community Update', $general->toMail($user)->subject);
        $this->assertSame('Your KushwahaConnect profile is approved', $approved->toMail($user)->subject);
    }
}
