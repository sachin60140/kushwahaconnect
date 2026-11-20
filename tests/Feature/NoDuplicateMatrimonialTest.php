<?php

namespace Tests\Feature;

use App\Models\MatrimonialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Inertia\Testing\AssertableInertia as Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The public matrimonial form must not create a second profile for the same person,
 * and admins can find (and delete) the duplicates already stored.
 */
class NoDuplicateMatrimonialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // SMS off: VerifiedMobile is not enforced (see MandatoryMobileOtpTest).
        config(['services.sms.driver' => 'log']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'profile_for' => 'Self', 'full_name' => 'Priya Kumari', 'gender' => 'Female', 'dob' => '1998-05-14',
            'marital_status' => 'Divorced', 'height' => '5 ft 3 in', 'education' => 'M.Sc.',
            'occupation' => 'Teacher', 'father_name' => 'Ram Prasad', 'family_location' => 'Gaya',
            'current_city' => 'Patna', 'mobile' => '9982211163', 'email' => 'priya@example.com', 'consent' => true,
        ], $overrides);
    }

    private function profile(array $attributes = []): MatrimonialProfile
    {
        return MatrimonialProfile::create(array_merge([
            'profile_for' => 'Self', 'full_name' => 'Priya Kumari', 'gender' => 'Female', 'dob' => '1998-05-14',
            'marital_status' => 'Divorced', 'mobile' => '9982211163', 'status' => 'pending',
        ], $attributes));
    }

    public static function liveStatuses(): array
    {
        return ['pending' => ['pending'], 'approved' => ['approved']];
    }

    #[DataProvider('liveStatuses')]
    public function test_same_mobile_and_name_in_another_format_is_rejected(string $status): void
    {
        $this->post(route('matrimonial.store'), $this->payload(['mobile' => '+91 99822 11163']))
            ->assertSessionHasNoErrors();
        MatrimonialProfile::query()->update(['status' => $status]);

        $this->post(route('matrimonial.store'), $this->payload([
            'mobile' => '9982211163',
            'full_name' => '  priya   KUMARI ',
            'marital_status' => 'Awaiting Divorce',
        ]))->assertSessionHasErrors(['mobile' => 'mat_error_duplicate_profile']);

        $this->assertSame(1, MatrimonialProfile::count());
    }

    public function test_re_application_is_allowed_after_rejection(): void
    {
        $this->profile(['status' => 'rejected']);

        $this->post(route('matrimonial.store'), $this->payload())->assertSessionHasNoErrors();

        $this->assertSame(2, MatrimonialProfile::count());
    }

    public function test_same_mobile_with_a_different_name_is_allowed(): void
    {
        $this->post(route('matrimonial.store'), $this->payload(['profile_for' => 'Daughter', 'full_name' => 'Priya Kumari']))
            ->assertSessionHasNoErrors();
        $this->post(route('matrimonial.store'), $this->payload(['profile_for' => 'Son', 'full_name' => 'Rahul Kumar', 'gender' => 'Male']))
            ->assertSessionHasNoErrors();

        $this->assertSame(2, MatrimonialProfile::count());
    }

    public function test_second_self_profile_on_the_same_mobile_is_rejected(): void
    {
        $this->profile(['profile_for' => 'Self', 'full_name' => 'Priya Kumari']);

        $this->post(route('matrimonial.store'), $this->payload(['profile_for' => 'Self', 'full_name' => 'Priya Kushwaha']))
            ->assertSessionHasErrors(['mobile' => 'mat_error_duplicate_self']);

        $this->assertSame(1, MatrimonialProfile::count());
    }

    public function test_logged_in_member_with_a_profile_is_sent_to_their_editor(): void
    {
        $user = User::factory()->create(['mobile' => '9811111111']);
        $this->profile(['user_id' => $user->id, 'mobile' => '9811111111', 'full_name' => 'Someone Else']);

        $this->actingAs($user)
            ->post(route('matrimonial.store'), $this->payload(['full_name' => 'Brand New Name', 'mobile' => '9822222222']))
            ->assertRedirect(route('user.matrimonial-profile.edit'))
            ->assertSessionHas('status', 'mat_already_have_profile');

        $this->assertSame(1, MatrimonialProfile::count());
    }

    public function test_stored_mobile_is_normalised(): void
    {
        $this->post(route('matrimonial.store'), $this->payload(['mobile' => '+91 99822-11163']))
            ->assertSessionHasNoErrors();

        $this->assertSame('9982211163', MatrimonialProfile::firstOrFail()->mobile);
    }

    public function test_member_editor_normalises_the_mobile(): void
    {
        $user = User::factory()->create(['mobile' => '9811111111']);
        $this->profile(['user_id' => $user->id, 'mobile' => '9811111111', 'status' => 'approved']);

        $this->actingAs($user)
            ->post(route('user.matrimonial-profile.update'), $this->payload(['mobile' => '+91 98111 11111']))
            ->assertSessionHasNoErrors();

        $this->assertSame('9811111111', MatrimonialProfile::where('user_id', $user->id)->value('mobile'));
    }

    public function test_admin_list_marks_duplicates_and_filters_them(): void
    {
        // The reported case: three profiles for one person, plus a sibling and a stranger.
        $a = $this->profile(['marital_status' => 'Awaiting Divorce', 'created_at' => now()->subDays(3)]);
        $b = $this->profile(['full_name' => 'priya  kumari ', 'created_at' => now()->subDays(2)]);
        $c = $this->profile(['full_name' => 'PRIYA KUMARI', 'created_at' => now()->subDay()]);
        $sibling = $this->profile(['profile_for' => 'Son', 'full_name' => 'Rahul Kumar', 'gender' => 'Male']);
        $other = $this->profile(['mobile' => '9876500001', 'full_name' => 'Anita Devi']);
        // Two "Self" profiles on one mobile with different names are also the same person.
        $selfA = $this->profile(['mobile' => '9876500002', 'full_name' => 'Sunil Kumar', 'created_at' => now()->subDays(5)]);
        $selfB = $this->profile(['mobile' => '9876500002', 'full_name' => 'Sunil Kushwaha', 'created_at' => now()->subDays(4)]);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'approved']);

        $this->actingAs($admin)->get(route('admin.matrimonial'))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($a, $sibling, $other, $selfA) {
                $counts = collect($page->toArray()['props']['profiles']['data'])->pluck('duplicate_count', 'id');
                $this->assertSame(3, $counts[$a->id]);
                $this->assertSame(0, $counts[$sibling->id]);
                $this->assertSame(0, $counts[$other->id]);
                $this->assertSame(2, $counts[$selfA->id]);
            });

        $this->actingAs($admin)->get(route('admin.matrimonial', ['status' => 'duplicates']))
            ->assertOk()
            ->assertInertia(function (Assert $page) use ($a, $b, $c, $selfA, $selfB) {
                $ids = collect($page->toArray()['props']['profiles']['data'])->pluck('id')->all();
                // Only grouped duplicates, by mobile (9876500002 < 9982211163), oldest first.
                $this->assertSame([$selfA->id, $selfB->id, $a->id, $b->id, $c->id], $ids);
            });
    }

    public function test_migration_normalises_old_mobile_values(): void
    {
        Log::spy();
        $a = $this->profile(['mobile' => '+91 99822 11163']);
        $b = $this->profile(['mobile' => '099822-11163', 'full_name' => 'Priya K']);
        $c = $this->profile(['mobile' => '9876500001']);
        $d = $this->profile(['mobile' => 'not-a-number']);
        $before = $a->fresh()->updated_at;

        $migration = require database_path('migrations/2026_11_20_000001_normalize_matrimonial_profile_mobiles.php');
        $migration->up();

        $this->assertSame('9982211163', $a->fresh()->mobile);
        $this->assertSame('9982211163', $b->fresh()->mobile);
        $this->assertSame('9876500001', $c->fresh()->mobile);
        $this->assertSame('not-a-number', $d->fresh()->mobile);
        $this->assertEquals($before, $a->fresh()->updated_at);
        $this->assertSame(4, MatrimonialProfile::count());
        Log::shouldHaveReceived('warning')->withArgs(fn ($message, $context = []) => str_contains($message, 'matrimonial_profiles') && count($context['rows'] ?? []) === 1);
    }
}
