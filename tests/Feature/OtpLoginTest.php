<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\OtpLoginController;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\FakeSms;
use App\Services\Sms\SmsHealth;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Members sign in with a one-time SMS code to their registered mobile.
 */
class OtpLoginTest extends TestCase
{
    use RefreshDatabase;

    private FakeSms $sms;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms.driver' => 'smartping',
            'services.smartping.username' => 'test-user',
            'services.smartping.password' => 'test-pass',
        ]);
        $this->sms = new FakeSms;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(['mobile' => '9876500001', 'role' => 'user', ...$attributes]);
    }

    private function wrong(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_login_page_offers_otp_when_sms_is_available(): void
    {
        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page
            ->component('Auth/Login')
            ->where('otpLogin.available', true)
            ->where('otpLogin.mobile', null)
            ->where('otpLogin.codeLength', OtpService::LENGTH));
    }

    public function test_send_otp_to_existing_member(): void
    {
        $this->member();

        $this->from(route('login'))
            ->post(route('login.otp.generate'), ['mobile' => '+91 98765 00001'])
            ->assertRedirect(route('login'))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'otp_login_sent');

        $this->assertNotNull($this->sms->lastCodeTo('9876500001'));
        $this->assertDatabaseHas('mobile_otps', ['mobile' => '9876500001', 'purpose' => OtpService::PURPOSE_LOGIN]);

        // The page now shows the code step with timers, and never the code.
        $code = $this->sms->lastCodeTo('9876500001');
        $this->get(route('login'))
            ->assertDontSee($code)
            ->assertInertia(fn (Assert $page) => $page
                ->where('otpLogin.mobile', '9876500001')
                ->where('otpLogin.resend_in', fn ($s) => $s > 0)
                ->where('otpLogin.expires_in', fn ($s) => $s > 0));
    }

    public function test_unknown_number_gets_join_hint_and_no_sms(): void
    {
        $this->post(route('login.otp.generate'), ['mobile' => '9876599999'])
            ->assertSessionHasErrors(['mobile' => 'otp_login_no_account']);

        $this->assertCount(0, $this->sms->sent);
        $this->assertDatabaseMissing('users', ['mobile' => '9876599999']);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $this->post(route('login.otp.generate'), ['mobile' => '12345'])
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_format']);
    }

    public function test_blocked_account_cannot_get_or_use_a_code(): void
    {
        $this->member(['status' => 'blocked']);

        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])
            ->assertSessionHasErrors(['mobile' => 'otp_login_blocked']);
        $this->assertCount(0, $this->sms->sent);

        // No code was requested in this session: a generic answer only.
        $this->post(route('login.otp.verify'), ['mobile' => '9876500001', 'otp' => '123456'])
            ->assertSessionHasErrors(['otp' => 'otp_error_invalid']);
        $this->assertGuest();
    }

    public function test_wrong_code_is_rejected(): void
    {
        $this->member();
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001']);
        $code = $this->sms->lastCodeTo('9876500001');

        $this->post(route('login.otp.verify'), ['mobile' => '9876500001', 'otp' => $this->wrong($code)])
            ->assertSessionHasErrors(['otp' => 'otp_error_invalid']);

        $this->assertGuest();
        $this->assertDatabaseHas('login_histories', ['status' => 'failed']);
    }

    public function test_expired_code_is_rejected(): void
    {
        $this->member();
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001']);
        $code = $this->sms->lastCodeTo('9876500001');

        $this->travel(OtpService::TTL_MINUTES + 1)->minutes();

        $this->post(route('login.otp.verify'), ['mobile' => '9876500001', 'otp' => $code])
            ->assertSessionHasErrors(['otp' => 'otp_error_expired']);
        $this->assertGuest();
    }

    public function test_successful_login_regenerates_session_and_verifies_mobile(): void
    {
        $user = $this->member();
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001']);
        $code = $this->sms->lastCodeTo('9876500001');
        $sessionBefore = session()->getId();

        $this->post(route('login.otp.verify'), ['mobile' => '9876500001', 'otp' => $code, 'remember' => true])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($sessionBefore, session()->getId());
        $this->assertNull(session(OtpLoginController::SESSION_KEY));
        $this->assertNotNull($user->fresh()->mobile_verified_at);
        $this->assertNotNull($user->fresh()->remember_token);
        $this->assertDatabaseHas('login_histories', ['user_id' => $user->id, 'status' => 'success']);
        $this->assertDatabaseCount('mobile_otps', 0);
    }

    public function test_login_returns_to_the_intended_page(): void
    {
        $this->member();
        $this->get(route('user.connections.index'))->assertRedirect(route('login'));

        $this->post(route('login.otp.generate'), ['mobile' => '9876500001']);
        $code = $this->sms->lastCodeTo('9876500001');

        $this->post(route('login.otp.verify'), ['mobile' => '9876500001', 'otp' => $code])
            ->assertRedirect(route('user.connections.index'));
    }

    public function test_change_number_forgets_the_pending_number(): void
    {
        $this->member();
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001']);

        $this->from(route('login'))->post(route('login.otp.reset'))->assertRedirect(route('login'));

        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('otpLogin.mobile', null));
    }

    public function test_otp_login_unavailable_while_sms_is_off(): void
    {
        config(['services.sms.driver' => 'log']);
        $this->member();

        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('otpLogin.available', false));

        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])
            ->assertSessionHasErrors(['mobile' => 'otp_login_unavailable']);
        $this->assertCount(0, $this->sms->sent);
    }

    public function test_otp_login_unavailable_while_sms_is_degraded(): void
    {
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_FAILED', 'statusCode' => 6001])]);
        config(['services.smartping.base_url' => 'https://pgapi.sparc.smartping.io/fe/api/v1/send', 'services.smartping.sender_id' => 'TESTID']);
        (new \App\Services\Sms\SmartPingSms(\App\Services\Sms\SmsConfig::resolve()))->deliver('9876500002', 'x');
        $this->assertTrue(SmsHealth::isDegraded());
        $this->member();

        $this->get(route('login'))->assertInertia(fn (Assert $page) => $page->where('otpLogin.available', false));
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])
            ->assertStatus(302)
            ->assertSessionHasErrors(['mobile' => 'otp_login_unavailable']);
    }

    public function test_number_lookups_are_rate_limited_per_ip(): void
    {
        for ($i = 0; $i < OtpLoginController::MAX_LOOKUPS_PER_IP_PER_HOUR; $i++) {
            // Spread over minutes so the route throttle (per minute) is not what stops us.
            $this->travel(7)->seconds();
            $this->post(route('login.otp.generate'), ['mobile' => '98765'.str_pad((string) $i, 5, '0', STR_PAD_LEFT)])
                ->assertSessionHasErrors(['mobile' => 'otp_login_no_account']);
            if ($i % 9 === 8) {
                $this->travel(1)->minutes();
            }
        }

        $this->member();
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])
            ->assertSessionHasErrors(['mobile' => 'otp_error_rate_limited']);
        $this->assertCount(0, $this->sms->sent);
    }

    public function test_resend_respects_cooldown(): void
    {
        $this->member();
        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])->assertSessionHasNoErrors();

        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])
            ->assertSessionHasErrors(['mobile' => 'otp_error_cooldown']);
        $this->assertCount(1, $this->sms->sent);
    }
}
