<?php

namespace Tests\Feature;

use App\Mail\SmsDegradedAlert;
use App\Models\User;
use App\Services\Otp\VerifiedMobiles;
use App\Services\Sms\SmartPingSms;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * When SmartPing itself fails (balance, credentials, sender, DLT template,
 * repeated connection errors) OTP enforcement pauses, admins are alerted once,
 * and usage counters keep track of sends.
 */
class SmsFailureFallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms.driver' => 'smartping',
            'services.smartping.base_url' => 'https://pgapi.sparc.smartping.io/fe/api/v1/send',
            'services.smartping.username' => 'test-user',
            'services.smartping.password' => 'test-secret-pass',
            'services.smartping.sender_id' => 'TESTID',
        ]);
        Mail::fake();
    }

    private function gatewayFails(int $statusCode): void
    {
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_FAILED', 'statusCode' => $statusCode, 'description' => 'Rejected'])]);
    }

    private function deliver(string $mobile = '9876511111'): array
    {
        return (new SmartPingSms(SmsConfig::resolve()))->deliver($mobile, 'Hi! Your verification code is 123456.');
    }

    private function contactPayload(): array
    {
        return ['name' => 'Ravi', 'mobile' => '9876522222', 'message' => 'Hello'];
    }

    /** @return array<string, array{int}> */
    public static function triggerCodes(): array
    {
        return ['balance 6001' => [6001], 'auth 2070' => [2070], 'sender 2051' => [2051], 'template 7001' => [7001]];
    }

    #[DataProvider('triggerCodes')]
    public function test_gateway_side_errors_degrade_and_stop_enforcement(int $code): void
    {
        $this->assertTrue(VerifiedMobiles::enforced());
        $this->gatewayFails($code);

        // A real visitor's OTP request hits the failure.
        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876511111'])
            ->assertStatus(422)
            ->assertJson(['message' => 'otp_field_degraded', 'degraded' => true]);

        $this->assertTrue(SmsHealth::isDegraded());
        $this->assertSame((string) $code, SmsHealth::degraded()['reason']);
        $this->assertFalse(VerifiedMobiles::enforced());

        // Forms work without OTP and say why.
        $this->post(route('contact.store'), $this->contactPayload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_enquiries', 1);
        $this->get(route('contact'))->assertInertia(fn (Assert $page) => $page
            ->where('mobileOtp.required', false)
            ->where('mobileOtp.degraded', true));

        // Member pages too.
        $member = User::factory()->create(['mobile' => '9876533333', 'role' => 'user']);
        $this->actingAs($member)->get(route('jobs.create'))->assertOk();
    }

    public function test_visitor_whose_send_degrades_sms_can_submit_without_reloading(): void
    {
        $this->gatewayFails(6001);

        // The visitor's own send is the one that trips the fallback: the response says so.
        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876522222'])
            ->assertStatus(422)
            ->assertJson(['degraded' => true, 'message' => 'otp_field_degraded']);

        // Same session, no reload: the form is accepted without OTP.
        $this->post(route('contact.store'), $this->contactPayload())->assertSessionHasNoErrors();
        $this->assertDatabaseCount('contact_enquiries', 1);
    }

    public function test_send_failures_that_do_not_degrade_keep_the_normal_message(): void
    {
        $this->gatewayFails(2054);
        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876522222'])
            ->assertStatus(422)
            ->assertJson(['degraded' => false, 'message' => 'otp_error_send_failed']);

        Http::fake(['*' => Http::failedConnection()]);
        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876533333'])
            ->assertStatus(422)
            ->assertJson(['degraded' => false, 'message' => 'otp_error_send_failed']);

        $this->post(route('contact.store'), $this->contactPayload())
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_not_verified']);
    }

    public function test_invalid_number_error_does_not_degrade(): void
    {
        $this->gatewayFails(2054);

        $this->assertFalse($this->deliver()['ok']);

        $this->assertFalse(SmsHealth::isDegraded());
        $this->assertTrue(VerifiedMobiles::enforced());
        $this->post(route('contact.store'), $this->contactPayload())
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_not_verified']);
    }

    public function test_three_connection_errors_degrade(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->deliver('9876500001');
        $this->deliver('9876500002');
        $this->assertFalse(SmsHealth::isDegraded(), 'Two errors are not enough.');

        $this->deliver('9876500003');
        $this->assertTrue(SmsHealth::isDegraded());
        $this->assertSame(SmsHealth::REASON_CONNECTION, SmsHealth::degraded()['reason']);
    }

    public function test_connection_errors_spread_out_do_not_degrade(): void
    {
        Http::fake(['*' => Http::failedConnection()]);

        $this->deliver('9876500001');
        $this->deliver('9876500002');
        $this->travel(SmsHealth::CONNECTION_WINDOW_MINUTES + 1)->minutes();
        $this->deliver('9876500003');

        $this->assertFalse(SmsHealth::isDegraded());
    }

    public function test_server_errors_count_as_connection_failures(): void
    {
        Http::fake(['*' => Http::response('Bad gateway', 502)]);

        foreach (['9876500001', '9876500002', '9876500003'] as $mobile) {
            $this->deliver($mobile);
        }

        $this->assertTrue(SmsHealth::isDegraded());
    }

    public function test_degraded_window_ends_by_itself(): void
    {
        $this->gatewayFails(6001);
        $this->deliver();
        $this->assertTrue(SmsHealth::isDegraded());

        $this->travel(SmsHealth::DEGRADED_MINUTES + 1)->minutes();

        $this->assertFalse(SmsHealth::isDegraded());
        $this->assertTrue(VerifiedMobiles::enforced());
    }

    public function test_successful_send_clears_the_flag_early(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['state' => 'SUBMIT_FAILED', 'statusCode' => 6001, 'description' => 'Insufficient balance'])
            ->push(['state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200, 'transactionId' => 'T2'])]);

        $this->deliver();
        $this->assertTrue(SmsHealth::isDegraded());

        $this->assertTrue($this->deliver()['ok']);
        $this->assertFalse(SmsHealth::isDegraded());
        $this->assertTrue(VerifiedMobiles::enforced());
    }

    public function test_super_admin_can_retry_now(): void
    {
        $this->gatewayFails(2070);
        $this->deliver();
        $this->assertTrue(SmsHealth::isDegraded());

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.sms-settings.retry'))->assertForbidden();
        $this->assertTrue(SmsHealth::isDegraded());

        $super = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($super)->post(route('admin.sms-settings.retry'))
            ->assertRedirect(route('admin.sms-settings.show'))
            ->assertSessionHas('success', 'sms_health_retry_done');

        $this->assertFalse(SmsHealth::isDegraded());
        $this->assertTrue(VerifiedMobiles::enforced());
    }

    public function test_super_admins_get_one_email_per_episode(): void
    {
        User::factory()->create(['role' => 'super_admin', 'email' => 'owner@example.test']);
        User::factory()->create(['role' => 'admin', 'email' => 'staff@example.test']);
        $this->gatewayFails(6001);

        $this->deliver('9876500001');
        $this->deliver('9876500002');
        $this->deliver('9876500003');

        Mail::assertSent(SmsDegradedAlert::class, 1);
        Mail::assertSent(SmsDegradedAlert::class, fn ($mail) => $mail->hasTo('owner@example.test')
            && ! $mail->hasTo('staff@example.test')
            && $mail->reason === '6001');

        // A new episode after recovery sends a new alert.
        SmsHealth::clear();
        $this->deliver('9876500004');
        Mail::assertSent(SmsDegradedAlert::class, 2);
    }

    public function test_no_email_when_mail_is_not_configured(): void
    {
        config(['mail.default' => 'log']);
        User::factory()->create(['role' => 'super_admin', 'email' => 'owner@example.test']);
        $this->gatewayFails(6001);

        $this->deliver();

        $this->assertTrue(SmsHealth::isDegraded());
        Mail::assertNothingSent();
    }

    public function test_transitions_are_logged_without_secrets(): void
    {
        Log::spy();
        $this->gatewayFails(6001);
        $this->deliver();
        SmsHealth::clear();

        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => str_contains($message, 'degraded') && ($context['reason'] ?? null) === '6001');
        Log::shouldHaveReceived('info')->withArgs(fn ($message, $context = []) => str_contains($message, 'back to normal'));
        Log::shouldNotHaveReceived('warning', [\Mockery::on(fn ($m) => str_contains((string) $m, 'test-secret-pass')), \Mockery::any()]);
    }

    public function test_counters_increment(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200])
            ->push(['state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200])
            ->push(['state' => 'SUBMIT_FAILED', 'statusCode' => 2054, 'description' => 'Invalid number'])]);
        $this->deliver('9876500001');
        $this->deliver('9876500002');
        $this->deliver('9876500003');

        $usage = SmsHealth::usage();
        $this->assertSame(2, $usage['sent_today']);
        $this->assertSame(1, $usage['failed_today']);
        $this->assertSame(2, $usage['sent_month']);
        $this->assertNotNull($usage['last_success_at']);
        $this->assertSame('ok', $usage['status']);

        $this->travel(1)->days();
        $this->assertSame(0, SmsHealth::usage()['sent_today']);
    }

    public function test_status_is_off_when_sms_is_off(): void
    {
        config(['services.sms.driver' => 'log']);

        $this->assertSame('off', SmsHealth::usage()['status']);
    }

    public function test_admin_dashboard_shows_banner_and_counters(): void
    {
        Http::fake(['*' => Http::sequence()
            ->push(['state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200])
            ->push(['state' => 'SUBMIT_FAILED', 'statusCode' => 6001, 'description' => 'Insufficient balance'])]);
        $this->deliver();
        $this->deliver();

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('smsHealth.status', 'degraded')
                ->where('smsHealth.sent_today', 1)
                ->where('smsHealth.failed_today', 1)
                ->where('smsHealth.sent_month', 1)
                ->where('smsHealth.degraded.reason', '6001')
                ->where('smsHealth.degraded.reason_key', 'sms_degraded_reason_balance')
                ->has('smsHealth.degraded.since'));
    }

    public function test_sms_settings_page_shows_health(): void
    {
        $this->gatewayFails(7001);
        $this->deliver();
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)->get(route('admin.sms-settings.show'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('smsHealth.status', 'degraded')
                ->where('smsHealth.degraded.reason_key', 'sms_degraded_reason_template'));
    }
}
