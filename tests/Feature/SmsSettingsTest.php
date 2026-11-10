<?php

namespace Tests\Feature;

use App\Models\SmsSetting;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Sms\SmartPingSms;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsSender;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private const TEMPLATE = 'Hi! Your verification code is {otp}. It is valid for 10 minutes. Please keep it confidential. - Sars Infotech Pvt Ltd';

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin']);
    }

    private function validSettings(array $overrides = []): array
    {
        return [
            'enabled' => true,
            'username' => 'kc-user',
            'password' => 'Secret-Pass-987',
            'sender_id' => 'INSARS',
            'dlt_content_id' => '1707177677498830200',
            'dlt_principal_entity_id' => '1701166126846262605',
            'base_url' => 'https://pgapi.sparc.smartping.io/fe/api/v1/send',
            'otp_message' => self::TEMPLATE,
            ...$overrides,
        ];
    }

    private function saveSettings(array $overrides = []): SmsSetting
    {
        return SmsSetting::create($this->validSettings($overrides));
    }

    public function test_only_super_admins_can_view_and_update_sms_settings(): void
    {
        $member = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->get(route('admin.sms-settings.show'))->assertRedirect(route('login'));
        $this->actingAs($member)->get(route('admin.sms-settings.show'))->assertRedirect();
        $this->actingAs($admin)->get(route('admin.sms-settings.show'))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.sms-settings.update'), $this->validSettings())->assertForbidden();
        $this->actingAs($admin)->post(route('admin.sms-settings.test'), ['mobile' => '9876500001'])->assertForbidden();
        $this->assertDatabaseCount('sms_settings', 0);

        $this->actingAs($this->superAdmin())->get(route('admin.sms-settings.show'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/SmsSettings'));
    }

    public function test_password_is_stored_encrypted_and_never_sent_to_the_browser(): void
    {
        $super = $this->superAdmin();

        $this->actingAs($super)->post(route('admin.sms-settings.update'), $this->validSettings())
            ->assertRedirect(route('admin.sms-settings.show'))
            ->assertSessionHasNoErrors();

        $raw = DB::table('sms_settings')->value('password');
        $this->assertNotSame('Secret-Pass-987', $raw);
        $this->assertStringNotContainsString('Secret-Pass-987', $raw);
        $this->assertSame('Secret-Pass-987', Crypt::decryptString($raw));

        $this->actingAs($super)->get(route('admin.sms-settings.show'))
            ->assertDontSee('Secret-Pass-987')
            ->assertDontSee($raw)
            ->assertInertia(fn (Assert $page) => $page
                ->where('hasSavedPassword', true)
                ->missing('settings.password'));
    }

    public function test_blank_password_keeps_the_saved_one(): void
    {
        $super = $this->superAdmin();
        $this->actingAs($super)->post(route('admin.sms-settings.update'), $this->validSettings());

        $this->actingAs($super)->post(route('admin.sms-settings.update'), $this->validSettings([
            'password' => '',
            'username' => 'new-user',
        ]))->assertSessionHasNoErrors();

        $settings = SmsSetting::first();
        $this->assertSame('Secret-Pass-987', $settings->password);
        $this->assertSame('new-user', $settings->username);
    }

    public function test_credentials_never_appear_in_shared_inertia_props(): void
    {
        $this->saveSettings();
        $member = User::factory()->create(['role' => 'user']);

        foreach ([
            $this->get('/'),
            $this->get(route('join')),
            $this->actingAs($member)->get(route('dashboard')),
            $this->actingAs($this->superAdmin())->get(route('admin.settings.index')),
        ] as $response) {
            $response->assertOk()
                ->assertDontSee('Secret-Pass-987')
                ->assertDontSee('kc-user')
                ->assertInertia(fn (Assert $page) => $page
                    ->where('websiteSettings', fn ($settings) => collect($settings)->keys()
                        ->every(fn ($key) => ! str_contains((string) $key, 'sms') && ! str_contains((string) $key, 'password'))));
        }
    }

    public function test_template_must_contain_otp_placeholder(): void
    {
        $this->actingAs($this->superAdmin())
            ->post(route('admin.sms-settings.update'), $this->validSettings(['otp_message' => 'No code here']))
            ->assertSessionHasErrors(['otp_message' => 'sms_settings_error_template']);
    }

    public function test_saved_settings_win_over_env_config(): void
    {
        config([
            'services.sms.driver' => 'log',
            'services.smartping.username' => 'env-user',
            'services.smartping.password' => 'env-pass',
            'services.smartping.sender_id' => 'ENVSND',
        ]);

        $this->assertFalse(SmsConfig::resolve()['enabled']);
        $this->assertSame('env-user', SmsConfig::resolve()['username']);

        $this->saveSettings(['sender_id' => null]);
        $resolved = SmsConfig::resolve();

        $this->assertTrue($resolved['enabled']);
        $this->assertSame('kc-user', $resolved['username']);
        $this->assertSame('Secret-Pass-987', $resolved['password']);
        $this->assertSame('ENVSND', $resolved['sender_id']); // blank in DB falls back
        $this->assertTrue(SmsConfig::configured($resolved));
        $this->assertInstanceOf(SmartPingSms::class, app(SmsSender::class));

        SmsSetting::first()->update(['enabled' => false]);
        $this->assertFalse(SmsConfig::configured());
        $this->assertNotInstanceOf(SmartPingSms::class, app(SmsSender::class));
    }

    public function test_smartping_request_uses_confirmed_format_and_exact_template(): void
    {
        Http::fake(['*' => Http::response(['transactionId' => 205538399, 'state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200, 'description' => 'Message accepted successfully', 'pdu' => 1])]);
        $this->saveSettings();
        $user = User::factory()->create(['mobile' => '9876500001']);

        $this->actingAs($user)->post(route('mobile.verify.send'))->assertSessionHasNoErrors();

        Http::assertSent(function (HttpRequest $request) {
            $query = $request->data();

            // Form POST keeps the password and code out of the URL.
            return $request->method() === 'POST'
                && $request->isForm()
                && $request->url() === 'https://pgapi.sparc.smartping.io/fe/api/v1/send'
                && $query['username'] === 'kc-user'
                && $query['password'] === 'Secret-Pass-987'
                && $query['unicode'] === 'false'
                && $query['from'] === 'INSARS'
                && $query['to'] === '9876500001'
                && $query['dltContentId'] === '1707177677498830200'
                && $query['dltPrincipalEntityId'] === '1701166126846262605'
                && preg_match('/^Hi! Your verification code is (\d{6})\. It is valid for 10 minutes\. Please keep it confidential\. - Sars Infotech Pvt Ltd$/', $query['text']) === 1;
        });
    }

    public function test_http_200_with_failed_state_is_treated_as_failure(): void
    {
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_FAILED', 'statusCode' => 2070, 'description' => 'Authentication failure'])]);
        $this->saveSettings();
        $user = User::factory()->create(['mobile' => '9876500001']);

        $this->actingAs($user)->post(route('mobile.verify.send'))
            ->assertSessionHasErrors(['otp' => 'otp_error_send_failed']);
        $this->assertDatabaseCount('mobile_otps', 0);
    }

    public function test_unknown_response_shape_is_a_failure(): void
    {
        Http::fake(['*' => Http::response('OK', 200)]);
        $result = (new SmartPingSms($this->validSettings()))->deliver('9876500001', 'x');

        $this->assertFalse($result['ok']);
        $this->assertSame(200, $result['http']);
    }

    public function test_mobile_numbers_are_normalized(): void
    {
        $this->assertSame('9876500001', SmartPingSms::normalizeMobile('+91 98765 00001'));
        $this->assertSame('9876500001', SmartPingSms::normalizeMobile('09876500001'));
        $this->assertSame('9876500001', SmartPingSms::normalizeMobile('919876500001'));
        $this->assertNull(SmartPingSms::normalizeMobile('12345'));
        $this->assertNull(SmartPingSms::normalizeMobile('1234567890'));
    }

    public function test_connection_errors_never_leak_the_query_string(): void
    {
        Http::fake(fn () => throw new ConnectionException('cURL error 28 for https://pgapi.sparc.smartping.io/fe/api/v1/send?username=kc-user&password=Secret-Pass-987&text=123456'));
        Log::spy();

        $result = (new SmartPingSms($this->validSettings()))->deliver('9876500001', 'code 123456');

        $this->assertFalse($result['ok']);
        $this->assertStringNotContainsString('Secret-Pass-987', $result['description']);
        $this->assertStringContainsString('[query removed]', $result['description']);
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => ! str_contains(json_encode($context), 'Secret-Pass-987')
            && ! str_contains(json_encode($context), '9876500001'));
    }

    public function test_platform_budget_caps_real_sms(): void
    {
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_ACCEPTED'])]);
        for ($i = 0; $i < SmartPingSms::MAX_PER_HOUR; $i++) {
            RateLimiter::hit('sms-platform:hour', 3600);
        }

        $result = (new SmartPingSms($this->validSettings()))->deliver('9876500001', 'x');

        $this->assertFalse($result['ok']);
        Http::assertNothingSent();
        $this->assertSame(SmartPingSms::MAX_PER_HOUR, RateLimiter::attempts('sms-platform:hour'));
    }

    public function test_send_test_sms_shows_gateway_response(): void
    {
        Http::fake(['*' => Http::response(['state' => 'SUBMIT_FAILED', 'statusCode' => 2070, 'description' => 'Authentication failure'])]);
        $super = $this->superAdmin();
        $this->saveSettings(['enabled' => false]);

        $this->actingAs($super)->post(route('admin.sms-settings.test'), ['mobile' => '9876500001'])
            ->assertRedirect(route('admin.sms-settings.show'))
            ->assertSessionHas('sms_test_result', fn ($result) => $result['ok'] === false
                && $result['http'] === 200
                && $result['state'] === 'SUBMIT_FAILED'
                && $result['status_code'] === 2070
                && $result['description'] === 'Authentication failure'
                && $result['hint'] === 'sms_gateway_error_auth');

        Http::assertSentCount(1);
    }

    public function test_accepted_send_returns_transaction_id(): void
    {
        Http::fake(['*' => Http::response(['transactionId' => 205538399, 'state' => 'SUBMIT_ACCEPTED', 'statusCode' => 200, 'description' => 'Message accepted successfully', 'pdu' => 1])]);

        $result = (new SmartPingSms($this->validSettings()))->deliver('9876500001', 'x');

        $this->assertTrue($result['ok']);
        $this->assertSame('205538399', $result['transaction_id']);
        $this->assertNull($result['hint']);
    }

    public function test_known_error_codes_map_to_hints(): void
    {
        $this->assertSame('sms_gateway_error_sender', SmartPingSms::errorHint(2051));
        $this->assertSame('sms_gateway_error_number', SmartPingSms::errorHint(2054));
        $this->assertSame('sms_gateway_error_auth', SmartPingSms::errorHint(2070));
        $this->assertSame('sms_gateway_error_balance', SmartPingSms::errorHint(6001));
        $this->assertSame('sms_gateway_error_template', SmartPingSms::errorHint(7001));
        $this->assertNull(SmartPingSms::errorHint(9999));
    }

    public function test_otp_expiry_matches_template(): void
    {
        $this->assertSame(10, OtpService::TTL_MINUTES);
        $this->assertStringContainsString('10 minutes', config('services.smartping.otp_message'));
    }
}
