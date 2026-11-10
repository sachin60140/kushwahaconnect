<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\OtpLoginController;
use App\Mail\SmsDegradedAlert;
use App\Models\District;
use App\Models\LoginHistory;
use App\Models\MemberProfile;
use App\Models\MobileOtp;
use App\Models\State;
use App\Models\User;
use App\Services\Otp\OtpException;
use App\Services\Otp\OtpService;
use App\Services\Sms\FakeSms;
use App\Services\Sms\LogSms;
use App\Services\Sms\SmartPingSms;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsHealth;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * Regression tests for the OTP / login / SMS security review (findings A–M).
 */
class OtpSecurityReviewTest extends TestCase
{
    use RefreshDatabase;

    private FakeSms $sms;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms.driver' => 'smartping',
            'services.smartping.base_url' => 'https://pgapi.sparc.smartping.io/fe/api/v1/send',
            'services.smartping.username' => 'test-user',
            'services.smartping.password' => 'test-pass',
            'services.smartping.sender_id' => 'TESTID',
        ]);
        $this->sms = new FakeSms;
        $this->app->instance(SmsSender::class, $this->sms);
        Mail::fake();
    }

    private function member(string $mobile, string $role = 'user', bool $verified = true): MemberProfile
    {
        $user = User::factory()->create(['mobile' => $mobile, 'role' => $role]);
        if ($verified) {
            $user->forceFill(['mobile_verified_at' => now()])->save();
        }

        return MemberProfile::create(['user_id' => $user->id, 'full_name' => 'M '.$mobile, 'gender' => 'Male', 'mobile' => $mobile, 'status' => 'approved']);
    }

    private function wrong(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    // ---- A: only super admins can change a member's mobile ----

    public function test_a_admin_gets_403_and_does_not_see_the_edit_control(): void
    {
        $member = $this->member('9876500001');
        $super = $this->member('9876500002', 'super_admin');
        $admin = User::factory()->create(['role' => 'admin', 'mobile' => '9876500003']);

        foreach ([$member, $super] as $target) {
            $this->actingAs($admin)->patch(route('admin.members.mobile', $target->id), ['mobile' => '9876500009'])->assertForbidden();
        }
        $this->assertSame('9876500001', $member->user->fresh()->mobile);
        $this->assertSame('9876500002', $super->user->fresh()->mobile);

        $this->actingAs($admin)->get(route('admin.members'))
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where('canEditMobile', false));

        $superUser = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($superUser)->get(route('admin.members'))
            ->assertInertia(fn (\Inertia\Testing\AssertableInertia $page) => $page->where('canEditMobile', true));
    }

    public function test_a_super_admin_can_change_any_members_number_including_an_admins(): void
    {
        $member = $this->member('9876500004');
        $adminProfile = $this->member('9876500005', 'admin');
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)->patch(route('admin.members.mobile', $member->id), ['mobile' => '9876500006'])->assertSessionHasNoErrors();
        $this->actingAs($super)->patch(route('admin.members.mobile', $adminProfile->id), ['mobile' => '9876500007'])->assertSessionHasNoErrors();

        $this->assertSame('9876500006', $member->user->fresh()->mobile);
        $this->assertSame('9876500007', $adminProfile->user->fresh()->mobile);
        $this->assertNull($member->user->fresh()->mobile_verified_at, 'Verification is cleared so the member sees the reminder.');
    }

    public function test_a_after_a_super_admin_change_otp_login_works_and_verifies_the_number(): void
    {
        $member = $this->member('9876500010');
        $super = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($super)->patch(route('admin.members.mobile', $member->id), ['mobile' => '9876500011'])->assertSessionHasNoErrors();
        auth()->logout();
        $this->assertNull($member->user->fresh()->mobile_verified_at);

        $this->post(route('login.otp.generate'), ['mobile' => '9876500011'])->assertSessionHasNoErrors();
        $this->post(route('login.otp.verify'), ['mobile' => '9876500011', 'otp' => $this->sms->lastCodeTo('9876500011')])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($member->user);
        $this->assertNotNull($member->user->fresh()->mobile_verified_at);
    }

    public function test_a_existing_unverified_members_can_still_use_otp_login(): void
    {
        $this->member('9876500008', verified: false);

        $this->post(route('login.otp.generate'), ['mobile' => '9876500008'])->assertSessionHasNoErrors();
        $this->post(route('login.otp.verify'), ['mobile' => '9876500008', 'otp' => $this->sms->lastCodeTo('9876500008')])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }

    // ---- B: limits counted before sending, failed sends count, one send at a time ----

    public function test_b_failed_sends_still_count_towards_the_limit(): void
    {
        $this->sms->shouldFail = true;
        $otp = app(OtpService::class);

        for ($i = 0; $i < OtpService::MAX_SENDS_PER_MOBILE_PER_HOUR; $i++) {
            try {
                $otp->send('9876500010', OtpService::PURPOSE_VERIFY, '10.0.0.9');
            } catch (OtpException $e) {
                $this->assertSame(OtpService::SEND_FAILED, $e->reason);
            }
        }

        try {
            $otp->send('9876500010', OtpService::PURPOSE_VERIFY, '10.0.0.9');
            $this->fail('Expected the per-number limit.');
        } catch (OtpException $e) {
            $this->assertSame(OtpService::RATE_LIMITED, $e->reason);
        }
    }

    public function test_b_a_send_already_in_progress_blocks_a_parallel_one(): void
    {
        $lock = Cache::lock('otp-send:9876500011:'.OtpService::PURPOSE_VERIFY, 30);
        $this->assertTrue($lock->get());

        try {
            app(OtpService::class)->send('9876500011', OtpService::PURPOSE_VERIFY, '10.0.0.9');
            $this->fail('Expected cooldown while another send holds the lock.');
        } catch (OtpException $e) {
            $this->assertSame(OtpService::COOLDOWN, $e->reason);
        } finally {
            $lock->release();
        }

        $this->assertCount(0, $this->sms->sent);
    }

    // ---- C: guest form sends cannot use up the whole SMS budget ----

    public function test_c_form_share_runs_out_before_login_and_alerts_once(): void
    {
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200])]);
        User::factory()->create(['role' => 'super_admin', 'email' => 'owner@example.test']);

        $formLimit = (int) floor(SmartPingSms::MAX_PER_HOUR * SmartPingSms::FORM_SHARE);
        for ($i = 0; $i < $formLimit; $i++) {
            RateLimiter::hit('sms-platform:form:hour', 3600);
        }

        $gateway = new SmartPingSms(SmsConfig::resolve());
        $this->assertFalse($gateway->deliver('9876500012', 'x', 'form')['ok']);
        $this->assertFalse($gateway->deliver('9876500013', 'x', 'form')['ok']);
        $this->assertTrue($gateway->deliver('9876500014', 'x', 'login')['ok'], 'Login keeps its share.');

        $this->assertSame('sms-platform:form:hour', SmsHealth::usage()['cap_hit']['bucket']);
        Mail::assertSent(SmsDegradedAlert::class, 1);
        Mail::assertSent(SmsDegradedAlert::class, fn ($mail) => $mail->reason === 'cap');
    }

    // ---- D: login verify is bound to the number this session asked a code for ----

    public function test_d_verify_for_a_number_not_requested_here_is_generic_and_harmless(): void
    {
        $victim = $this->member('9876500015')->user;

        // The victim requests a code in their own browser.
        $this->post(route('login.otp.generate'), ['mobile' => '9876500015'])->assertSessionHasNoErrors();
        $code = $this->sms->lastCodeTo('9876500015');

        // An attacker with a fresh session guesses.
        $this->flushSession();
        $this->post(route('login.otp.verify'), ['mobile' => '9876500015', 'otp' => $this->wrong($code)])
            ->assertSessionHasErrors(['otp' => 'otp_error_invalid']);
        $this->post(route('login.otp.verify'), ['mobile' => '9876599999', 'otp' => '123456'])
            ->assertSessionHasErrors(['otp' => 'otp_error_invalid']);

        $this->assertSame(0, MobileOtp::where('mobile', '9876500015')->first()->attempts);
        $this->assertSame(0, LoginHistory::where('user_id', $victim->id)->where('status', 'failed')->count());
        $this->assertGuest();
    }

    public function test_d_verify_counts_towards_the_per_ip_lookup_limit(): void
    {
        for ($i = 0; $i < OtpLoginController::MAX_LOOKUPS_PER_IP_PER_HOUR; $i++) {
            RateLimiter::hit('otp-login-lookup:127.0.0.1', 3600);
        }

        $this->post(route('login.otp.verify'), ['mobile' => '9876500016', 'otp' => '123456'])
            ->assertSessionHasErrors(['otp' => 'otp_error_rate_limited']);
    }

    // ---- E: one stored format for mobiles ----

    private function joinPayload(string $mobile): array
    {
        $state = State::create(['name_en' => 'Bihar', 'name_hi' => 'Bihar', 'code' => 'BR', 'status' => true]);
        $district = District::create(['state_id' => $state->id, 'name_en' => 'Patna', 'name_hi' => 'Patna', 'status' => true]);

        return ['full_name' => 'New', 'gender' => 'Male', 'mobile' => $mobile, 'state_id' => $state->id, 'district_id' => $district->id, 'consent' => '1'];
    }

    public function test_e_join_stores_the_bare_number_and_catches_formatted_duplicates(): void
    {
        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876500017'])->assertOk();
        $this->postJson(route('mobile-otp.verify'), ['mobile' => '9876500017', 'code' => $this->sms->lastCodeTo('9876500017')])->assertOk();

        $this->post(route('join.store'), $this->joinPayload('+91 98765 00017'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['mobile' => '9876500017']);
        $this->assertDatabaseHas('member_profiles', ['mobile' => '9876500017']);
        auth()->logout();

        $this->post(route('join.store'), $this->joinPayload('098765-00017'))->assertSessionHasErrors('mobile');
        $this->assertSame(1, User::where('mobile', '9876500017')->count());
    }

    public function test_e_resaving_the_same_number_in_another_format_keeps_verification(): void
    {
        $profile = $this->member('9876500018');
        $user = $profile->user;

        $this->actingAs($user)->post(route('user.member-profile.update'), [
            'full_name' => 'M', 'gender' => 'Male', 'mobile' => '+91 98765 00018',
        ])->assertSessionHasNoErrors();

        $this->assertSame('9876500018', $user->fresh()->mobile);
        $this->assertNotNull($user->fresh()->mobile_verified_at);
    }

    public function test_e_migration_normalises_and_leaves_conflicts_alone(): void
    {
        $a = User::factory()->create(['mobile' => '+91 98765 00019']);
        $b = User::factory()->create(['mobile' => '9876500020']);
        $c = User::factory()->create(['mobile' => '+91-98765-00020']); // would duplicate $b
        $d = User::factory()->create(['mobile' => 'not-a-number']);
        Log::spy();

        $migration = require database_path('migrations/2026_11_11_000002_normalize_stored_mobile_numbers.php');
        $migration->up();

        $this->assertSame('9876500019', $a->fresh()->mobile);
        $this->assertSame('9876500020', $b->fresh()->mobile);
        $this->assertSame('+91-98765-00020', $c->fresh()->mobile);
        $this->assertSame('not-a-number', $d->fresh()->mobile);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => str_contains($message, 'users') && count($context['rows'] ?? []) === 2);
    }

    // ---- G: attempts are claimed atomically ----

    public function test_g_no_check_runs_once_the_attempt_budget_is_spent(): void
    {
        $otp = app(OtpService::class);
        $otp->send('9876500021', OtpService::PURPOSE_VERIFY);
        $code = $this->sms->lastCodeTo('9876500021');

        // As if parallel requests had already used every attempt.
        MobileOtp::where('mobile', '9876500021')->update(['attempts' => OtpService::MAX_ATTEMPTS]);

        try {
            $otp->verify('9876500021', OtpService::PURPOSE_VERIFY, $code);
            $this->fail('The right code must not work once attempts are spent.');
        } catch (OtpException $e) {
            $this->assertSame(OtpService::TOO_MANY_ATTEMPTS, $e->reason);
        }
        $this->assertDatabaseCount('mobile_otps', 0);
    }

    // ---- H: blocked accounts ----

    public function test_h_blocked_account_cannot_log_in_with_a_password(): void
    {
        User::factory()->create(['mobile' => '9876500022', 'status' => 'blocked', 'password' => bcrypt('secret-pass')]);

        $this->post(route('login'), ['email' => '9876500022', 'password' => 'secret-pass'])
            ->assertSessionHasErrors(['email' => 'otp_login_blocked']);
        $this->assertGuest();
    }

    public function test_h_existing_session_ends_when_the_account_is_blocked(): void
    {
        $user = User::factory()->create(['mobile' => '9876500023', 'role' => 'user']);
        $this->actingAs($user)->get(route('profile.edit'))->assertOk();

        $user->forceFill(['status' => 'blocked'])->save();

        $this->actingAs($user->fresh())->get(route('profile.edit'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    // ---- I: per-purpose per-number limits ----

    public function test_i_form_codes_cannot_use_up_a_numbers_login_codes(): void
    {
        $otp = app(OtpService::class);

        for ($i = 0; $i < OtpService::MAX_SENDS_PER_MOBILE_PER_HOUR; $i++) {
            $otp->send('9876500024', OtpService::PURPOSE_FORM, '10.0.1.'.$i);
            $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
        }

        $otp->send('9876500024', OtpService::PURPOSE_LOGIN, '10.0.2.1');
        $this->assertCount(OtpService::MAX_SENDS_PER_MOBILE_PER_HOUR + 1, $this->sms->sent);
    }

    public function test_i_overall_per_number_cap_applies_across_purposes(): void
    {
        $otp = app(OtpService::class);
        $sent = 0;

        foreach ([OtpService::PURPOSE_FORM, OtpService::PURPOSE_LOGIN, OtpService::PURPOSE_VERIFY] as $purpose) {
            for ($i = 0; $i < 3; $i++) {
                try {
                    $otp->send('9876500025', $purpose, '10.0.3.'.$sent);
                    $sent++;
                } catch (OtpException $e) {
                    $this->assertSame(OtpService::RATE_LIMITED, $e->reason);
                }
                $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
            }
        }

        $this->assertSame(OtpService::MAX_SENDS_PER_MOBILE_ALL_PURPOSES_PER_HOUR, $sent);
    }

    // ---- J: blank .env values fall back to defaults ----

    public function test_j_blank_env_values_do_not_override_defaults(): void
    {
        foreach (['SMARTPING_BASE_URL', 'SMARTPING_OTP_MESSAGE', 'SMARTPING_SENDER_ID'] as $key) {
            $_ENV[$key] = '';
            $_SERVER[$key] = '';
            putenv("{$key}=");
        }

        try {
            $config = require config_path('services.php');
        } finally {
            foreach (['SMARTPING_BASE_URL', 'SMARTPING_OTP_MESSAGE', 'SMARTPING_SENDER_ID'] as $key) {
                unset($_ENV[$key], $_SERVER[$key]);
                putenv($key);
            }
        }

        $this->assertSame('https://pgapi.sparc.smartping.io/fe/api/v1/send', $config['smartping']['base_url']);
        $this->assertStringContainsString('{otp}', $config['smartping']['otp_message']);
        $this->assertSame('INSARS', $config['smartping']['sender_id']);
    }

    // ---- K: API URL restricted to SmartPing; credentials scrubbed from logs ----

    public function test_k_only_smartping_https_urls_are_accepted(): void
    {
        $this->assertTrue(SmartPingSms::allowedBaseUrl('https://pgapi.sparc.smartping.io/fe/api/v1/send'));
        foreach (['http://pgapi.sparc.smartping.io/x', 'https://evil.example/x', 'https://smartping.io.evil.example/x',
            'https://user:pw@pgapi.sparc.smartping.io/x', 'https://pgapi.sparc.smartping.io:8443/x', 'https://169.254.169.254/'] as $url) {
            $this->assertFalse(SmartPingSms::allowedBaseUrl($url), $url);
        }

        $super = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($super)->post(route('admin.sms-settings.update'), ['enabled' => true, 'base_url' => 'https://internal.example/send'])
            ->assertSessionHasErrors(['base_url' => 'sms_settings_error_url']);

        config(['services.smartping.base_url' => 'https://internal.example/send']);
        Http::fake();
        $this->assertFalse((new SmartPingSms(SmsConfig::resolve()))->deliver('9876500026', 'x')['ok']);
        Http::assertNothingSent();

        $this->assertStringNotContainsString('secret', SmartPingSms::stripQuery('cURL error for https://kc:secret@pgapi.sparc.smartping.io/send'));
    }

    // ---- L: the log driver never writes codes or full numbers in production ----

    public function test_l_log_driver_outside_local_masks_the_number_and_skips_the_text(): void
    {
        $this->app['env'] = 'production';
        Log::spy();

        (new LogSms)->send('9876500027', 'Your verification code is 482913.');

        Log::shouldNotHaveReceived('info');
        Log::shouldHaveReceived('warning')->withArgs(function ($message, $context = []) {
            $all = $message.json_encode($context);

            return ! str_contains($all, '482913') && ! str_contains($all, '9876500027');
        });
    }

    // ---- M: degraded mode does not let guests touch the gateway ----

    public function test_m_guest_form_sends_skip_the_gateway_while_degraded_and_do_not_extend_it(): void
    {
        $this->app->forgetInstance(SmsSender::class);
        $this->app->bind(SmsSender::class, fn () => new SmartPingSms(SmsConfig::resolve()));
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_FAILED', 'statusCode' => 6001])]);

        (new SmartPingSms(SmsConfig::resolve()))->deliver('9876500028', 'x');
        $until = SmsHealth::degraded()['until'];
        Http::fake(); // fresh recorder

        $this->travel(5)->minutes();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson(route('mobile-otp.send'), ['mobile' => '98765000'.(30 + $i)])
                ->assertStatus(422)
                ->assertJson(['degraded' => true]);
        }

        Http::assertNothingSent();
        $this->assertSame($until, SmsHealth::degraded()['until'], 'Guest traffic does not extend the window.');

        $this->travel(SmsHealth::DEGRADED_MINUTES)->minutes();
        $this->assertFalse(SmsHealth::isDegraded());
    }
}
