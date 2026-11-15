<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\LoginHistory;
use App\Models\MatrimonialProfile;
use App\Models\MemberProfile;
use App\Models\MobileOtp;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Mockery;
use Tests\TestCase;

class SuperAdminMemberDetailsTest extends TestCase
{
    use RefreshDatabase;

    private State $state;

    private District $district;

    protected function setUp(): void
    {
        parent::setUp();
        $this->state = State::create(['name_en' => 'Bihar', 'name_hi' => 'बिहार']);
        $this->district = District::create(['state_id' => $this->state->id, 'name_en' => 'Patna', 'name_hi' => 'पटना']);
    }

    private function member(string $mobile = '9876511111', array $profile = []): MemberProfile
    {
        $user = User::factory()->create(['mobile' => $mobile, 'role' => 'user', 'name' => 'Ram Kumar', 'email' => 'ram@example.com']);
        $user->forceFill(['mobile_verified_at' => now(), 'status' => 'approved'])->save();
        MobileOtp::create(['mobile' => $mobile, 'purpose' => 'login', 'code_hash' => 'secret-otp-hash', 'expires_at' => now()->addMinutes(5), 'last_sent_at' => now()]);

        return MemberProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Ram Kumar',
            'gender' => 'Male',
            'mobile' => $mobile,
            'email' => 'ram@example.com',
            'status' => 'approved',
            'state_id' => $this->state->id,
            'district_id' => $this->district->id,
            ...$profile,
        ]);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['role' => 'super_admin', 'mobile' => '9876599999']);
    }

    private function payload(MemberProfile $member, array $overrides = []): array
    {
        return [
            'full_name' => $member->full_name,
            'gender' => 'Male',
            'mobile' => $member->mobile,
            'email' => $member->email,
            'state_id' => $member->state_id,
            'district_id' => $member->district_id,
            'status' => 'approved',
            'account_status' => 'approved',
            'volunteer_interest' => false,
            'join_district_team' => false,
            'is_public' => false,
            'show_mobile' => false,
            'allow_whatsapp' => true,
            ...$overrides,
        ];
    }

    public function test_super_admin_can_view_the_complete_member_record(): void
    {
        $member = $this->member();
        LoginHistory::create(['user_id' => $member->user_id, 'ip_address' => '10.0.0.1', 'login_at' => now(), 'status' => 'success']);
        MatrimonialProfile::create([
            'user_id' => $member->user_id, 'profile_for' => 'self', 'full_name' => 'Ram Kumar', 'gender' => 'Male',
            'dob' => '1995-01-01', 'marital_status' => 'never_married', 'mobile' => $member->mobile, 'status' => 'pending',
        ]);

        $this->actingAs($this->superAdmin())->get(route('admin.members.show', $member->id))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/MemberShow')
                ->where('member.full_name', 'Ram Kumar')
                ->where('member.district_name', 'Patna')
                ->where('account.mobile', '9876511111')
                ->where('account.role', 'user')
                ->whereNot('account.mobile_verified_at', null)
                ->where('privacy.customised', false)
                ->has('linked.matrimonial', 1)
                ->has('logins', 1)
                ->where('logins.0.ip_address', '10.0.0.1'));
    }

    public function test_sensitive_fields_are_never_in_the_props(): void
    {
        $member = $this->member();
        $member->user->forceFill(['remember_token' => 'remember-me-token'])->save();

        $super = $this->superAdmin();
        foreach (['admin.members.show', 'admin.members.edit'] as $name) {
            $response = $this->actingAs($super)->get(route($name, $member->id))->assertOk();
            $props = $response->viewData('page')['props'];
            $json = json_encode([$props['member'] ?? null, $props['account'] ?? null, $props['privacy'] ?? null, $props['linked'] ?? null, $props['logins'] ?? null]);

            foreach (['password', 'remember_token', 'otp_code', 'otp_expires_at', 'code_hash'] as $key) {
                $this->assertArrayNotHasKey($key, $props['account'], "$name exposes $key");
                $this->assertStringNotContainsString('"'.$key.'"', $json, "$name exposes $key");
            }
            $this->assertStringNotContainsString('remember-me-token', $json);
            $this->assertStringNotContainsString('secret-otp-hash', $json);
            $this->assertStringNotContainsString($member->user->password, $json);
            $this->assertArrayNotHasKey('smsSettings', $props);
        }
    }

    public function test_admin_role_gets_403_on_view_edit_and_update(): void
    {
        $member = $this->member();
        $admin = User::factory()->create(['role' => 'admin', 'mobile' => '9876522222']);

        $this->actingAs($admin)->get(route('admin.members.show', $member->id))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.members.edit', $member->id))->assertForbidden();
        $this->actingAs($admin)->put(route('admin.members.update', $member->id), $this->payload($member, ['full_name' => 'Hacked']))->assertForbidden();
        $this->assertSame('Ram Kumar', $member->fresh()->full_name);
    }

    public function test_plain_user_is_turned_away(): void
    {
        $member = $this->member();
        $user = User::factory()->create(['role' => 'user', 'mobile' => '9876533333']);

        foreach ([
            $this->actingAs($user)->get(route('admin.members.show', $member->id)),
            $this->actingAs($user)->get(route('admin.members.edit', $member->id)),
            $this->actingAs($user)->put(route('admin.members.update', $member->id), $this->payload($member, ['full_name' => 'Hacked'])),
        ] as $response) {
            $this->assertTrue($response->isForbidden() || $response->isRedirect(), 'Expected 403 or redirect, got '.$response->status());
        }
        $this->assertSame('Ram Kumar', $member->fresh()->full_name);
    }

    public function test_update_changes_profile_and_account_and_keeps_them_in_sync(): void
    {
        $member = $this->member();
        $super = $this->superAdmin();

        $this->actingAs($super)->get(route('admin.members.edit', $member->id))
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Admin/MemberEdit')->where('account.role', 'user'));

        $this->actingAs($super)->put(route('admin.members.update', $member->id), $this->payload($member, [
            'full_name' => 'Ram Kumar Kushwaha',
            'email' => 'ramk@example.com',
            'city_village' => 'Danapur',
            'father_husband_name' => 'Shyam',
            'account_status' => 'suspended',
            'role' => 'super_admin', // ignored: role is not editable here
            'admin_note' => 'Checked by phone',
        ]))->assertRedirect(route('admin.members.show', $member->id))->assertSessionHasNoErrors();

        $member->refresh();
        $user = $member->user->fresh();
        $this->assertSame('Ram Kumar Kushwaha', $member->full_name);
        $this->assertSame('Danapur', $member->city_village);
        $this->assertSame('Shyam', $member->father_husband_name);
        $this->assertSame('Checked by phone', $member->admin_note);
        $this->assertSame('ramk@example.com', $member->email);
        $this->assertSame('Ram Kumar Kushwaha', $user->name);
        $this->assertSame('ramk@example.com', $user->email);
        $this->assertSame('suspended', $user->status);
        $this->assertSame('user', $user->role);
        $this->assertNotNull($user->mobile_verified_at, 'Unchanged mobile keeps its verification.');
    }

    public function test_email_clash_is_rejected(): void
    {
        $member = $this->member();
        User::factory()->create(['email' => 'taken@example.com', 'mobile' => '9876544444']);

        $this->actingAs($this->superAdmin())->put(route('admin.members.update', $member->id), $this->payload($member, ['email' => 'taken@example.com']))
            ->assertSessionHasErrors(['email' => 'admin_member_email_taken']);

        $this->assertSame('ram@example.com', $member->fresh()->email);
        $this->assertSame('ram@example.com', $member->user->fresh()->email);
    }

    public function test_mobile_change_normalises_and_clears_verification(): void
    {
        $member = $this->member();

        $this->actingAs($this->superAdmin())->put(route('admin.members.update', $member->id), $this->payload($member, ['mobile' => '+91 98765 77777']))
            ->assertSessionHasNoErrors();

        $this->assertSame('9876577777', $member->fresh()->mobile);
        $this->assertSame('9876577777', $member->user->fresh()->mobile);
        $this->assertNull($member->user->fresh()->mobile_verified_at);
    }

    public function test_mobile_taken_by_another_account_is_rejected(): void
    {
        $member = $this->member();
        User::factory()->create(['mobile' => '9876588888']);

        $this->actingAs($this->superAdmin())->put(route('admin.members.update', $member->id), $this->payload($member, ['mobile' => '9876588888']))
            ->assertSessionHasErrors(['mobile' => 'admin_mobile_taken']);
        $this->assertSame('9876511111', $member->user->fresh()->mobile);
    }

    public function test_photo_upload_replaces_and_remove_clears_the_photo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('profile_photos/old.jpg', 'old');
        $member = $this->member('9876511111', ['profile_photo' => 'profile_photos/old.jpg']);
        $super = $this->superAdmin();

        $this->actingAs($super)->put(route('admin.members.update', $member->id), $this->payload($member, [
            'profile_photo' => UploadedFile::fake()->image('new.jpg', 300, 300),
        ]))->assertSessionHasNoErrors();

        $path = $member->fresh()->profile_photo;
        $this->assertNotSame('profile_photos/old.jpg', $path);
        Storage::disk('public')->assertExists($path);
        Storage::disk('public')->assertMissing('profile_photos/old.jpg');

        $this->actingAs($super)->put(route('admin.members.update', $member->id), $this->payload($member->fresh(), ['remove_photo' => true]))
            ->assertSessionHasNoErrors();
        $this->assertNull($member->fresh()->profile_photo);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_update_logs_changed_field_names_without_values(): void
    {
        $member = $this->member();
        $super = $this->superAdmin();
        Log::spy();

        $this->actingAs($super)->put(route('admin.members.update', $member->id), $this->payload($member, [
            'city_village' => 'Secret Village',
            'email' => 'new@example.com',
        ]))->assertSessionHasNoErrors();

        Log::shouldHaveReceived('info')->with('Super admin updated a member.', Mockery::on(function (array $context) use ($member, $super) {
            $this->assertSame($super->id, $context['admin_id']);
            $this->assertSame($member->id, $context['member_profile_id']);
            $this->assertEqualsCanonicalizing(['city_village', 'email', 'user.email', 'user.email_verified_at'], $context['changed_fields']);
            $this->assertStringNotContainsString('Secret Village', json_encode($context));
            $this->assertStringNotContainsString('new@example.com', json_encode($context));

            return true;
        }))->once();
    }

    public function test_members_list_shows_view_link_only_to_super_admins(): void
    {
        $this->member();
        $admin = User::factory()->create(['role' => 'admin', 'mobile' => '9876522222']);

        $this->actingAs($admin)->get(route('admin.members'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canViewDetails', false));
        $this->actingAs($this->superAdmin())->get(route('admin.members'))
            ->assertInertia(fn (AssertableInertia $page) => $page->where('canViewDetails', true)->where('auth.user.role', 'super_admin'));

        $vue = file_get_contents(resource_path('js/Pages/Admin/Members.vue'));
        $this->assertStringContainsString("route('admin.members.show', member.id)", $vue);
        $this->assertMatchesRegularExpression('/v-if="isSuperAdmin"[\s\S]*admin\.members\.show/', $vue);
    }

    public function test_quick_mobile_edit_still_works_through_the_shared_rules(): void
    {
        $member = $this->member();

        $this->actingAs($this->superAdmin())->patch(route('admin.members.mobile', $member->id), ['mobile' => '+919876566666'])
            ->assertSessionHasNoErrors();
        $this->assertSame('9876566666', $member->user->fresh()->mobile);
        $this->assertNull($member->user->fresh()->mobile_verified_at);
    }
}
