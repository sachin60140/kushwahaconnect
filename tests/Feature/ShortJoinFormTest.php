<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\MemberProfile;
use App\Models\State;
use App\Models\User;
use App\Services\Sms\FakeSms;
use App\Services\Sms\SmsSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Join asks only for name, mobile (OTP), gender, state, district and consent;
 * the rest is completed later from the community profile.
 */
class ShortJoinFormTest extends TestCase
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

    private function shortPayload(string $mobile = '9876500001', array $overrides = []): array
    {
        $state = State::create(['name_en' => 'Bihar', 'name_hi' => 'बिहार', 'code' => 'BR', 'status' => true]);
        $district = District::create(['state_id' => $state->id, 'name_en' => 'Patna', 'name_hi' => 'पटना', 'status' => true]);

        return [
            'full_name' => 'Asha Kumari',
            'gender' => 'Female',
            'mobile' => $mobile,
            'state_id' => $state->id,
            'district_id' => $district->id,
            'consent' => '1',
            ...$overrides,
        ];
    }

    private function verifyInline(string $mobile): void
    {
        $this->postJson(route('mobile-otp.send'), ['mobile' => $mobile])->assertOk();
        $this->postJson(route('mobile-otp.verify'), ['mobile' => $mobile, 'code' => $this->sms->lastCodeTo($mobile)])->assertOk();
    }

    public function test_joining_with_only_the_short_fields_succeeds(): void
    {
        $this->verifyInline('9876500001');

        $this->post(route('join.store'), $this->shortPayload())
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('mobile', '9876500001')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertSame('approved', $user->status);
        $this->assertSame('Asha Kumari', $user->name);
        $this->assertNull($user->email);
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertNotEmpty($user->password, 'An unusable random password is stored.');

        $profile = MemberProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('approved', $profile->status);
        $this->assertSame('Female', $profile->gender);
        $this->assertNull($profile->city_village);
        $this->assertNull($profile->current_address);
        $this->assertNull($profile->pin_code);
    }

    public function test_joining_is_rejected_without_otp_while_sms_is_on(): void
    {
        $this->post(route('join.store'), $this->shortPayload())
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_not_verified']);

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_optional_password_can_be_set_and_used(): void
    {
        $this->verifyInline('9876500001');

        $this->post(route('join.store'), $this->shortPayload('9876500001', [
            'password' => 'my-secret-pass',
            'password_confirmation' => 'my-secret-pass',
        ]))->assertSessionHasNoErrors();

        $user = User::where('mobile', '9876500001')->firstOrFail();
        $this->assertTrue(Hash::check('my-secret-pass', $user->password));

        auth()->logout();
        $this->post(route('login'), ['email' => '9876500001', 'password' => 'my-secret-pass'])
            ->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticatedAs($user);
    }

    public function test_mismatched_optional_password_is_rejected(): void
    {
        $this->verifyInline('9876500001');

        $this->post(route('join.store'), $this->shortPayload('9876500001', [
            'password' => 'my-secret-pass',
            'password_confirmation' => 'different-pass',
        ]))->assertSessionHasErrors('password');
    }

    public function test_password_is_required_when_sms_is_off(): void
    {
        config(['services.sms.driver' => 'log']);

        $this->post(route('join.store'), $this->shortPayload())
            ->assertSessionHasErrors('password');
        $this->assertDatabaseCount('users', 0);

        $this->post(route('join.store'), $this->shortPayload('9876500002', [
            'password' => 'my-secret-pass',
            'password_confirmation' => 'my-secret-pass',
        ]))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['mobile' => '9876500002']);
    }

    public function test_dashboard_lists_missing_details_and_hides_card_once_complete(): void
    {
        $this->verifyInline('9876500001');
        $this->post(route('join.store'), $this->shortPayload());
        $user = User::where('mobile', '9876500001')->firstOrFail();

        $this->actingAs($user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('profileChecklist.missing', MemberProfile::CHECKLIST_FIELDS)
                ->where('profileChecklist.percent', 0));

        Storage::fake('public');
        $profile = MemberProfile::where('user_id', $user->id)->firstOrFail();

        $this->actingAs($user)->post(route('user.member-profile.update'), [
            'full_name' => 'Asha Kumari',
            'father_husband_name' => 'Ram Prasad',
            'gender' => 'Female',
            'dob' => '1995-04-01',
            'mobile' => '9876500001',
            'whatsapp' => '9876500001',
            'email' => 'asha@example.test',
            'state_id' => $profile->state_id,
            'district_id' => $profile->district_id,
            'city_village' => 'Patna',
            'pin_code' => '800001',
            'current_address' => 'Boring Road',
            'profile_photo' => UploadedFile::fake()->image('me.jpg', 600, 600),
        ])->assertSessionHasNoErrors();

        $this->assertNotNull($profile->fresh()->profile_photo);
        Storage::disk('public')->assertExists($profile->fresh()->profile_photo);

        $this->actingAs($user->fresh())->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('profileChecklist.missing', [])
                ->where('profileChecklist.percent', 100));
    }

    public function test_admin_list_and_directory_render_with_empty_optional_fields(): void
    {
        $this->verifyInline('9876500001');
        $this->post(route('join.store'), $this->shortPayload('9876500001', ['is_public' => '1']));
        auth()->logout();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.members'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Members')
                ->has('members.data', 1)
                ->where('members.data.0.full_name', 'Asha Kumari')
                ->where('members.data.0.city_village', null));

        $this->actingAs($admin)->get(route('admin.members.export'))->assertOk();

        auth()->logout();
        $this->get(route('directory'))->assertOk();
        $this->get(route('welcome'))->assertOk();
    }

    public function test_join_page_renders(): void
    {
        $this->get(route('join'))->assertOk()->assertInertia(fn (Assert $page) => $page->component('Join'));
    }
}
