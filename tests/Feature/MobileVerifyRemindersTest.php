<?php

namespace Tests\Feature;

use App\Models\MemberProfile;
use App\Models\User;
use App\Support\MobileVerifyReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Members who joined before OTP was mandatory are reminded to verify their
 * mobile, and admins can find, remind and correct them.
 */
class MobileVerifyRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.sms.driver' => 'smartping',
            'services.smartping.username' => 'test-user',
            'services.smartping.password' => 'test-pass',
        ]);
    }

    private function member(string $mobile, bool $verified, array $profile = []): MemberProfile
    {
        $user = User::factory()->create(['mobile' => $mobile, 'role' => 'user']);
        if ($verified) {
            $user->forceFill(['mobile_verified_at' => now()])->save();
        }

        return MemberProfile::create([
            'user_id' => $user->id,
            'full_name' => 'Member '.substr($mobile, -4),
            'gender' => 'male',
            'mobile' => $mobile,
            'status' => 'approved',
            ...$profile,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_dashboard_reminds_unverified_members(): void
    {
        $member = $this->member('9876500001', false);

        $this->actingAs($member->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Dashboard')
                ->where('mobileVerifyReminder', true));
    }

    public function test_dashboard_has_no_reminder_for_verified_members(): void
    {
        $member = $this->member('9876500001', true);

        $this->actingAs($member->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('mobileVerifyReminder', false));
    }

    public function test_reminder_is_hidden_while_sms_is_off(): void
    {
        config(['services.sms.driver' => 'log']);
        $member = $this->member('9876500001', false);

        $this->actingAs($member->user)->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page->where('mobileVerifyReminder', false));
    }

    public function test_admin_list_shows_badges_filter_and_count(): void
    {
        $this->member('9876500001', false);
        $this->member('9876500002', false);
        $this->member('9876500003', true);
        // A profile without an account has nothing to verify.
        MemberProfile::create(['full_name' => 'No Account', 'gender' => 'male', 'mobile' => '9876500004', 'status' => 'approved']);

        $this->actingAs($this->admin())->get(route('admin.members'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Members')
                ->where('unverifiedMobileCount', 2)
                ->has('members.data', 4)
                ->where('members.data', fn ($rows) => collect($rows)->where('mobile_verified', true)->count() === 1
                    && collect($rows)->where('has_account', false)->count() === 1
                    && collect($rows)->every(fn ($row) => ! array_key_exists('user', $row) || $row['user'] === null)));

        $this->actingAs($this->admin())->get(route('admin.members', ['mobile' => 'unverified']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.mobile', 'unverified')
                ->has('members.data', 2)
                ->where('members.data', fn ($rows) => collect($rows)->every(fn ($row) => $row['mobile_verified'] === false && $row['has_account'] === true)));
    }

    public function test_admin_dashboard_shows_unverified_count(): void
    {
        $this->member('9876500001', false);
        $this->member('9876500002', true);

        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Dashboard')
                ->where('stats.unverified_mobiles', 1));
    }

    public function test_whatsapp_reminder_link_contains_the_verify_url(): void
    {
        $this->member('9876500001', false, ['full_name' => 'Ramesh Kumar', 'whatsapp' => '9876511111']);
        $this->member('9876500002', true);

        $this->actingAs($this->admin())->get(route('admin.members'))
            ->assertInertia(fn (Assert $page) => $page->where('members.data', function ($rows) {
                $rows = collect($rows);
                $link = $rows->firstWhere('full_name', 'Ramesh Kumar')['verify_reminder_link'];
                $text = rawurldecode(substr($link, strpos($link, '?text=') + 6));

                return str_starts_with($link, 'https://wa.me/919876511111?text=')
                    && str_contains($text, route('mobile.verify.show'))
                    && str_contains($text, 'नमस्ते Ramesh Kumar')
                    && $rows->firstWhere('mobile_verified', true)['verify_reminder_link'] === null;
            }));
    }

    public function test_reminder_link_falls_back_to_mobile(): void
    {
        $this->assertStringStartsWith('https://wa.me/919876500001?text=', MobileVerifyReminder::whatsappLink(null, '9876500001', 'X'));
        $this->assertNull(MobileVerifyReminder::whatsappLink(null, '123', 'X'));
    }

    public function test_admin_mobile_change_resets_verification(): void
    {
        $member = $this->member('9876500001', true);

        $this->actingAs($this->admin())
            ->patch(route('admin.members.mobile', $member->id), ['mobile' => '9876500009'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'admin_mobile_updated');

        $this->assertSame('9876500009', $member->fresh()->mobile);
        $user = $member->user->fresh();
        $this->assertSame('9876500009', $user->mobile);
        $this->assertNull($user->mobile_verified_at);
    }

    public function test_admin_mobile_change_is_validated(): void
    {
        $member = $this->member('9876500001', true);
        $this->member('9876500002', true);

        $this->actingAs($this->admin())
            ->patch(route('admin.members.mobile', $member->id), ['mobile' => '9876500002'])
            ->assertSessionHasErrors(['mobile' => 'admin_mobile_taken']);

        $this->actingAs($this->admin())
            ->patch(route('admin.members.mobile', $member->id), ['mobile' => '12345'])
            ->assertSessionHasErrors(['mobile' => 'otp_error_mobile_format']);

        $this->assertNotNull($member->user->fresh()->mobile_verified_at);
    }

    public function test_members_cannot_change_mobiles_through_admin_route(): void
    {
        $member = $this->member('9876500001', true);

        $this->actingAs($member->user)
            ->patch(route('admin.members.mobile', $member->id), ['mobile' => '9876500009']);

        // The admin middleware turns members away (it redirects rather than 403s).
        $this->assertSame('9876500001', $member->user->fresh()->mobile);
        $this->assertNotNull($member->user->fresh()->mobile_verified_at);
    }
}
