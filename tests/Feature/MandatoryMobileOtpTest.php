<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\State;
use App\Models\User;
use App\Services\Otp\OtpService;
use App\Services\Otp\VerifiedMobiles;
use App\Services\Sms\FakeSms;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Mobile numbers on public forms and member pages must be confirmed by OTP
 * while SMS is on; nothing is enforced while SMS is off.
 */
class MandatoryMobileOtpTest extends TestCase
{
    use RefreshDatabase;

    private FakeSms $sms;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sms = new FakeSms;
        $this->app->instance(SmsSender::class, $this->sms);
        $this->smsOn();
    }

    private function smsOn(): void
    {
        config([
            'services.sms.driver' => 'smartping',
            'services.smartping.username' => 'test-user',
            'services.smartping.password' => 'test-pass',
        ]);
    }

    private function smsOff(): void
    {
        config(['services.sms.driver' => 'log']);
    }

    /** Run the inline OTP step for $mobile, as MobileOtpField.vue does. */
    private function verifyInline(string $mobile): void
    {
        $this->postJson(route('mobile-otp.send'), ['mobile' => $mobile])
            ->assertOk()
            ->assertJson(['sent' => true]);

        $code = $this->sms->lastCodeTo($mobile);

        $this->postJson(route('mobile-otp.verify'), ['mobile' => $mobile, 'code' => $code])
            ->assertOk()
            ->assertJson(['verified' => true]);
    }

    /**
     * route name, mobile field, table that receives the row.
     *
     * @return array<string, array{string, string, string}>
     */
    public static function guardedForms(): array
    {
        return [
            'join' => ['join.store', 'mobile', 'users'],
            'matrimonial' => ['matrimonial.store', 'mobile', 'matrimonial_profiles'],
            'help request' => ['help.store', 'mobile', 'help_requests'],
            'volunteer' => ['volunteer.store', 'mobile', 'volunteers'],
            'contact' => ['contact.store', 'mobile', 'contact_enquiries'],
            'nomination' => ['nominations.store', 'nominator_mobile', 'personality_nominations'],
            'correction request' => ['corrections.store', 'requester_mobile', 'profile_correction_requests'],
            'data deletion' => ['data-deletion.store', 'mobile', 'data_deletion_requests'],
        ];
    }

    private function payload(string $route, string $mobile): array
    {
        return match ($route) {
            'join.store' => (function () use ($mobile) {
                $state = State::create(['name_en' => 'Bihar', 'name_hi' => 'Bihar', 'code' => 'BR', 'status' => true]);
                $district = District::create(['state_id' => $state->id, 'name_en' => 'Patna', 'name_hi' => 'Patna', 'status' => true]);

                return [
                    'full_name' => 'New Member', 'gender' => 'Male', 'mobile' => $mobile,
                    'state_id' => $state->id, 'district_id' => $district->id,
                    'consent' => '1',
                    'password' => 'password', 'password_confirmation' => 'password',
                ];
            })(),
            'matrimonial.store' => [
                'profile_for' => 'Self', 'full_name' => 'Priya Kumari', 'gender' => 'Female', 'dob' => '1998-05-14',
                'marital_status' => 'Never Married', 'height' => '5 ft 3 in', 'education' => 'M.Sc.',
                'occupation' => 'Teacher', 'father_name' => 'Ram Prasad', 'family_location' => 'Gaya',
                'mobile' => $mobile, 'consent' => true,
            ],
            'help.store' => [
                'name' => 'Asha', 'mobile' => $mobile, 'state' => 'Bihar', 'district' => 'Patna', 'city' => 'Patna',
                'category' => 'Medical', 'title' => 'Help needed', 'description' => 'Details of the request.', 'urgency' => 'Normal',
            ],
            'volunteer.store' => ['name' => 'Ravi', 'mobile' => $mobile, 'state' => 'Bihar', 'district' => 'Patna', 'city' => 'Patna'],
            'contact.store' => ['name' => 'Ravi', 'mobile' => $mobile, 'message' => 'Hello'],
            'nominations.store' => [
                'person_name' => 'Inspiring Person', 'category' => 'Education', 'achievement' => 'Scholarships.',
                'nominator_name' => 'Member', 'nominator_mobile' => $mobile, 'consent' => true,
            ],
            'corrections.store' => [
                'profile_type' => 'personality', 'profile_name' => 'Inspiring Person', 'requester_name' => 'Verifier',
                'requester_mobile' => $mobile, 'correction_details' => 'Please update the designation.',
            ],
            'data-deletion.store' => ['name' => 'Member', 'mobile' => $mobile, 'reason' => 'Delete my data.'],
        };
    }

    #[DataProvider('guardedForms')]
    public function test_form_is_rejected_without_otp(string $route, string $field, string $table): void
    {
        $before = \DB::table($table)->count();

        $this->post(route($route), $this->payload($route, '9876511111'))
            ->assertSessionHasErrors([$field => 'otp_error_mobile_not_verified']);

        $this->assertSame($before, \DB::table($table)->count());
    }

    #[DataProvider('guardedForms')]
    public function test_form_is_rejected_for_a_different_number_than_the_verified_one(string $route, string $field, string $table): void
    {
        $this->verifyInline('9876511111');
        $before = \DB::table($table)->count();

        $this->post(route($route), $this->payload($route, '9876522222'))
            ->assertSessionHasErrors([$field => 'otp_error_mobile_not_verified']);

        $this->assertSame($before, \DB::table($table)->count());
    }

    #[DataProvider('guardedForms')]
    public function test_form_is_accepted_after_verification(string $route, string $field, string $table): void
    {
        $this->verifyInline('9876511111');
        $before = \DB::table($table)->count();

        // Formatting differences (+91, spaces) still match the verified number.
        $this->post(route($route), $this->payload($route, '+91 98765 11111'))
            ->assertSessionHasNoErrors();

        $this->assertSame($before + 1, \DB::table($table)->count());
    }

    #[DataProvider('guardedForms')]
    public function test_form_is_not_enforced_while_sms_is_off(string $route, string $field, string $table): void
    {
        $this->smsOff();
        Log::spy();
        $before = \DB::table($table)->count();

        $this->post(route($route), $this->payload($route, '9876533333'))
            ->assertSessionHasNoErrors();

        $this->assertSame($before + 1, \DB::table($table)->count());
        Log::shouldHaveReceived('warning')->withArgs(fn ($message) => str_contains($message, 'NOT enforced'));
    }

    public function test_verification_proof_expires_after_thirty_minutes(): void
    {
        $this->verifyInline('9876511111');
        $this->travel(VerifiedMobiles::VALID_MINUTES + 1)->minutes();

        $this->post(route('contact.store'), $this->payload('contact.store', '9876511111'))
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_not_verified']);
    }

    public function test_inline_otp_rejects_wrong_code_and_never_returns_the_code(): void
    {
        $response = $this->postJson(route('mobile-otp.send'), ['mobile' => '9876511111'])->assertOk();
        $code = $this->sms->lastCodeTo('9876511111');
        $this->assertStringNotContainsString($code, $response->getContent());

        $this->postJson(route('mobile-otp.verify'), ['mobile' => '9876511111', 'code' => $code === '000000' ? '111111' : '000000'])
            ->assertStatus(422)
            ->assertJson(['message' => 'otp_error_invalid']);

        $this->post(route('contact.store'), $this->payload('contact.store', '9876511111'))
            ->assertSessionHasErrors('mobile');
    }

    public function test_inline_otp_shares_cooldown_and_rate_limits(): void
    {
        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876511111'])->assertOk();

        $this->postJson(route('mobile-otp.send'), ['mobile' => '9876511111'])
            ->assertStatus(422)
            ->assertJson(['message' => 'otp_error_cooldown']);

        $this->postJson(route('mobile-otp.send'), ['mobile' => '12345'])
            ->assertStatus(422)
            ->assertJson(['message' => 'otp_error_mobile_format']);

        $this->assertCount(1, $this->sms->sent);
        $this->assertDatabaseHas('mobile_otps', ['mobile' => '9876511111', 'purpose' => OtpService::PURPOSE_FORM]);
    }

    public function test_join_with_verified_number_starts_verified(): void
    {
        $this->verifyInline('9876511111');

        $this->post(route('join.store'), $this->payload('join.store', '9876511111'))
            ->assertRedirect(route('dashboard'));

        $this->assertNotNull(User::where('mobile', '9876511111')->first()->mobile_verified_at);
    }

    public function test_signed_in_members_own_verified_number_needs_no_new_otp(): void
    {
        $user = User::factory()->create(['mobile' => '9876544444']);
        $user->forceFill(['mobile_verified_at' => now()])->save();

        $this->actingAs($user)
            ->post(route('volunteer.store'), $this->payload('volunteer.store', '9876544444'))
            ->assertSessionHasNoErrors();
    }

    public function test_shared_props_say_whether_otp_is_required(): void
    {
        $this->get(route('contact'))->assertInertia(fn (Assert $page) => $page
            ->where('mobileOtp.required', true)
            ->where('mobileOtp.verified', []));

        $this->smsOff();

        $this->get(route('contact'))->assertInertia(fn (Assert $page) => $page
            ->where('mobileOtp.required', false));
    }

    /** @return array<string, array{string}> */
    public static function memberPages(): array
    {
        return [
            'community profile' => ['user.member-profile.edit'],
            'matrimonial profile' => ['user.matrimonial-profile.edit'],
            'privacy settings' => ['user.privacy-settings.edit'],
            'connections' => ['user.connections.index'],
            'business' => ['businesses.create'],
            'jobs' => ['jobs.create'],
        ];
    }

    #[DataProvider('memberPages')]
    public function test_member_pages_redirect_unverified_members(string $routeName): void
    {
        $user = User::factory()->create(['mobile' => '9876555555', 'role' => 'user']);

        $this->actingAs($user)->get(route($routeName))
            ->assertRedirect(route('mobile.verify.show'))
            ->assertSessionHas('status', 'otp_required_member');

        $user->forceFill(['mobile_verified_at' => now()])->save();

        $this->actingAs($user)->get(route($routeName))->assertOk();
    }

    public function test_member_returns_to_the_page_they_wanted_after_verifying(): void
    {
        $user = User::factory()->create(['mobile' => '9876555555', 'role' => 'user']);

        $this->actingAs($user)->get(route('jobs.create'))->assertRedirect(route('mobile.verify.show'));

        $this->actingAs($user)->post(route('mobile.verify.send'));
        $code = $this->sms->lastCodeTo('9876555555');

        $this->actingAs($user)->post(route('mobile.verify'), ['code' => $code])
            ->assertRedirect(route('jobs.create'));
    }

    public function test_member_pages_are_open_while_sms_is_off(): void
    {
        $this->smsOff();
        $user = User::factory()->create(['mobile' => '9876555555', 'role' => 'user']);

        $this->actingAs($user)->get(route('user.member-profile.edit'))->assertOk();
    }

    public function test_admins_are_not_redirected(): void
    {
        $admin = User::factory()->create(['mobile' => '9876566666', 'role' => 'admin']);

        $this->actingAs($admin)->get(route('jobs.create'))->assertOk();
    }

    public function test_account_settings_can_no_longer_change_the_mobile_without_otp(): void
    {
        $user = User::factory()->create(['mobile' => '9876577777', 'role' => 'user']);
        $user->forceFill(['mobile_verified_at' => now()])->save();

        // PATCH /profile ignores 'mobile': only the community profile (with OTP) changes it.
        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'mobile' => '9876588888',
            'language_preference' => 'hi',
        ])->assertSessionHasNoErrors();

        $this->assertSame('9876577777', $user->fresh()->mobile);
        $this->assertNotNull($user->fresh()->mobile_verified_at);
    }

    public function test_changed_mobile_locks_member_pages_until_verified(): void
    {
        $user = User::factory()->create(['mobile' => '9876577777', 'role' => 'user']);
        $user->forceFill(['mobile_verified_at' => now()])->save();

        $user->update(['mobile' => '9876588888']);

        $this->assertNull($user->fresh()->mobile_verified_at);
        $this->actingAs($user->fresh())->get(route('user.member-profile.edit'))
            ->assertRedirect(route('mobile.verify.show'));
    }

    public function test_community_profile_number_change_needs_inline_otp(): void
    {
        $user = User::factory()->create(['mobile' => '9876577777', 'role' => 'user']);
        $user->forceFill(['mobile_verified_at' => now()])->save();
        $profile = ['full_name' => 'Member', 'gender' => 'Male'];

        $this->actingAs($user)->post(route('user.member-profile.update'), [...$profile, 'mobile' => '9876599999'])
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_not_verified']);
        $this->assertSame('9876577777', $user->fresh()->mobile);

        $this->verifyInline('9876599999');

        $this->actingAs($user)->post(route('user.member-profile.update'), [...$profile, 'mobile' => '9876599999'])
            ->assertSessionHasNoErrors();

        $fresh = $user->fresh();
        $this->assertSame('9876599999', $fresh->mobile);
        $this->assertNotNull($fresh->mobile_verified_at, 'A number confirmed inline keeps the account verified.');
    }
}
