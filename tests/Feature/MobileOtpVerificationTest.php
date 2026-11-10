<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\MobileOtp;
use App\Models\State;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\FakeSms;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MobileOtpVerificationTest extends TestCase
{
    use RefreshDatabase;

    private FakeSms $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new FakeSms;
        $this->app->instance(SmsSender::class, $this->sms);
    }

    private function member(array $attributes = []): User
    {
        return User::factory()->create(['mobile' => '9876500001', ...$attributes]);
    }

    private function wrongCode(string $code): string
    {
        return $code === '000000' ? '111111' : '000000';
    }

    public function test_verify_page_shows_masked_number(): void
    {
        $user = $this->member();

        $this->actingAs($user)->get(route('mobile.verify.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Auth/VerifyMobile')
                ->where('mobile', '••••••0001')
                ->where('verified', false));
    }

    public function test_send_stores_hashed_code_and_sends_sms(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post(route('mobile.verify.send'))
            ->assertRedirect(route('mobile.verify.show'))
            ->assertSessionHasNoErrors();

        $this->assertCount(1, $this->sms->sent);
        $code = $this->sms->lastCodeTo('9876500001');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $otp = MobileOtp::where('mobile', '9876500001')->where('purpose', OtpService::PURPOSE_VERIFY)->firstOrFail();
        $this->assertNotSame($code, $otp->code_hash);
        $this->assertTrue(Hash::check($code, $otp->code_hash));
        $this->assertTrue($otp->expires_at->between(now()->addMinutes(9), now()->addMinutes(10)));
    }

    public function test_correct_code_marks_mobile_verified(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post(route('mobile.verify.send'));
        $code = $this->sms->lastCodeTo('9876500001');

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $code])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertNotNull($user->fresh()->mobile_verified_at);
        $this->assertDatabaseCount('mobile_otps', 0);
    }

    public function test_wrong_code_is_rejected_and_counted(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post(route('mobile.verify.send'));
        $code = $this->sms->lastCodeTo('9876500001');

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $this->wrongCode($code)])
            ->assertSessionHasErrors(['code' => 'otp_error_invalid']);

        $this->assertNull($user->fresh()->mobile_verified_at);
        $this->assertSame(1, MobileOtp::first()->attempts);
    }

    public function test_code_is_burned_after_max_wrong_attempts(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post(route('mobile.verify.send'));
        $code = $this->sms->lastCodeTo('9876500001');

        for ($i = 1; $i < OtpService::MAX_ATTEMPTS; $i++) {
            $this->actingAs($user)->post(route('mobile.verify'), ['code' => $this->wrongCode($code)])
                ->assertSessionHasErrors(['code' => 'otp_error_invalid']);
        }

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $this->wrongCode($code)])
            ->assertSessionHasErrors(['code' => 'otp_error_too_many_attempts']);

        // The right code no longer works once the attempt budget is spent.
        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $code])
            ->assertSessionHasErrors('code');
        $this->assertNull($user->fresh()->mobile_verified_at);
    }

    public function test_expired_code_is_rejected(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post(route('mobile.verify.send'));
        $code = $this->sms->lastCodeTo('9876500001');

        $this->travel(OtpService::TTL_MINUTES + 1)->minutes();

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $code])
            ->assertSessionHasErrors(['code' => 'otp_error_expired']);

        $this->assertNull($user->fresh()->mobile_verified_at);
        $this->assertDatabaseCount('mobile_otps', 0);
    }

    public function test_badly_formatted_code_is_rejected(): void
    {
        $user = $this->member();

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => '12ab'])
            ->assertSessionHasErrors(['code' => 'otp_error_code_format']);
    }

    public function test_resend_respects_cooldown_and_replaces_old_code(): void
    {
        $user = $this->member();
        $this->actingAs($user)->post(route('mobile.verify.send'));
        $first = $this->sms->lastCodeTo('9876500001');

        $this->actingAs($user)->post(route('mobile.verify.send'))
            ->assertSessionHasErrors(['otp' => 'otp_error_cooldown']);
        $this->assertCount(1, $this->sms->sent);

        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->actingAs($user)->post(route('mobile.verify.send'))->assertSessionHasNoErrors();
        $this->assertCount(2, $this->sms->sent);
        $second = $this->sms->lastCodeTo('9876500001');

        if ($first !== $second) {
            $this->actingAs($user)->post(route('mobile.verify'), ['code' => $first])
                ->assertSessionHasErrors('code');
        }

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $second])
            ->assertSessionHasNoErrors();
        $this->assertNotNull($user->fresh()->mobile_verified_at);
    }

    public function test_sends_are_rate_limited_per_number(): void
    {
        $user = $this->member();

        for ($i = 0; $i < OtpService::MAX_SENDS_PER_MOBILE_PER_HOUR; $i++) {
            $this->actingAs($user)->post(route('mobile.verify.send'))->assertSessionHasNoErrors();
            $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();
        }

        $this->actingAs($user)->post(route('mobile.verify.send'))
            ->assertSessionHasErrors(['otp' => 'otp_error_rate_limited']);
        $this->assertCount(OtpService::MAX_SENDS_PER_MOBILE_PER_HOUR, $this->sms->sent);
    }

    public function test_sends_are_rate_limited_per_ip(): void
    {
        $otp = app(OtpService::class);

        for ($i = 0; $i < OtpService::MAX_SENDS_PER_IP_PER_HOUR; $i++) {
            $otp->send('98765000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT), OtpService::PURPOSE_VERIFY, '10.0.0.1');
        }

        $user = $this->member(['mobile' => '9876599999']);

        $this->actingAs($user)
            ->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])
            ->post(route('mobile.verify.send'))
            ->assertSessionHasErrors(['otp' => 'otp_error_rate_limited']);
    }

    public function test_sms_failure_is_reported_without_storing_a_code(): void
    {
        $this->sms->shouldFail = true;
        $user = $this->member();

        $this->actingAs($user)->post(route('mobile.verify.send'))
            ->assertSessionHasErrors(['otp' => 'otp_error_send_failed']);

        $this->assertDatabaseCount('mobile_otps', 0);
    }

    public function test_changing_mobile_clears_verification(): void
    {
        $user = $this->member();
        $user->forceFill(['mobile_verified_at' => now()])->save();

        $user->update(['mobile' => '9876500002']);

        $this->assertNull($user->fresh()->mobile_verified_at);
    }

    public function test_code_is_not_logged_or_returned(): void
    {
        Log::spy();
        $user = $this->member();

        $response = $this->actingAs($user)->post(route('mobile.verify.send'));
        $code = $this->sms->lastCodeTo('9876500001');

        $this->assertStringNotContainsString($code, $response->getContent());
        $this->actingAs($user)->get(route('mobile.verify.show'))
            ->assertDontSee($code);
        Log::shouldNotHaveReceived('info');
    }

    public function test_guests_cannot_use_verification_routes(): void
    {
        $this->get(route('mobile.verify.show'))->assertRedirect(route('login'));
        $this->post(route('mobile.verify.send'))->assertRedirect(route('login'));
        $this->assertCount(0, $this->sms->sent);
    }

    private function joinPayload(string $mobile): array
    {
        $state = State::create(['name_en' => 'Bihar', 'name_hi' => 'Bihar', 'code' => 'BR', 'status' => true]);
        $district = District::create(['state_id' => $state->id, 'name_en' => 'Patna', 'name_hi' => 'Patna', 'status' => true]);

        return [
            'full_name' => 'New Member',
            'gender' => 'Male',
            'mobile' => $mobile,
            'state_id' => $state->id,
            'district_id' => $district->id,
            'city_village' => 'Patna',
            'pin_code' => '800001',
            'current_address' => 'Patna',
            'consent' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ];
    }

    public function test_joining_sends_a_verification_code(): void
    {
        $this->post(route('join.store'), $this->joinPayload('9876500003'))
            ->assertRedirect(route('mobile.verify.show'))
            ->assertSessionHas('status', 'otp_join_sent');

        $this->assertNotNull($this->sms->lastCodeTo('9876500003'));
        $this->assertDatabaseHas('mobile_otps', ['mobile' => '9876500003', 'purpose' => OtpService::PURPOSE_VERIFY]);
    }

    public function test_join_still_succeeds_when_sms_fails(): void
    {
        $this->sms->shouldFail = true;
        $this->post(route('join.store'), $this->joinPayload('9876500004'))
            ->assertRedirect(route('mobile.verify.show'))
            ->assertSessionHas('status', 'otp_join_send_later');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['mobile' => '9876500004']);
    }

    public function test_otp_login_marks_mobile_verified(): void
    {
        // OTP login is offered only while SMS is on.
        config(['services.sms.driver' => 'smartping', 'services.smartping.username' => 'test-user', 'services.smartping.password' => 'test-pass']);
        $user = $this->member();

        $this->post(route('login.otp.generate'), ['mobile' => '9876500001'])->assertSessionHasNoErrors();
        $code = $this->sms->lastCodeTo('9876500001');

        $this->post(route('login.otp.verify'), ['mobile' => '9876500001', 'otp' => $code])
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->mobile_verified_at);
    }
}
