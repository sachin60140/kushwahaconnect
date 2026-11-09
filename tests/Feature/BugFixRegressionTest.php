<?php

namespace Tests\Feature;

use App\Exports\ContactExport;
use App\Exports\MatrimonialExport;
use App\Exports\MembersExport;
use App\Models\ContactEnquiry;
use App\Models\District;
use App\Models\MatrimonialProfile;
use App\Models\MemberProfile;
use App\Models\State;
use App\Models\User;
use App\Models\UserPrivacySetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BugFixRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_matrimonial_page_lists_full_details_and_id_proof_link(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('matrimonial_ids/proof.pdf', 'pdf-bytes');

        $profile = MatrimonialProfile::create($this->matrimonialData([
            'father_name' => 'Father Name',
            'id_proof' => 'matrimonial_ids/proof.pdf',
        ]));

        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('admin.matrimonial', ['status' => 'pending']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Matrimonial')
                ->where('filters.status', 'pending')
                ->where('profiles.data.0.mobile', '9000000002')
                ->where('profiles.data.0.father_name', 'Father Name')
                ->where('profiles.data.0.id_proof_url', route('admin.matrimonial.id-proof', $profile->id)));

        $this->actingAs($admin)
            ->get(route('admin.matrimonial.id-proof', $profile->id))
            ->assertOk();

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get(route('admin.matrimonial.id-proof', $profile->id))
            ->assertRedirect(route('welcome'));
    }

    public function test_admin_can_approve_matrimonial_profile(): void
    {
        $profile = MatrimonialProfile::create($this->matrimonialData());

        $this->actingAs($this->admin())
            ->from(route('admin.matrimonial'))
            ->patch(route('admin.matrimonial.status', $profile->id), ['status' => 'approved'])
            ->assertRedirect(route('admin.matrimonial'))
            ->assertSessionHasNoErrors();

        $this->assertSame('approved', $profile->fresh()->status);
    }

    public function test_secure_file_route_is_admin_only(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('matrimonial_ids/proof.pdf', 'pdf-bytes');

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->get('/secure-file/matrimonial_ids/proof.pdf')
            ->assertRedirect(route('welcome'));
    }

    public function test_public_directory_hides_contact_details_and_respects_privacy(): void
    {
        [$state, $district] = $this->location();

        $visibleUser = User::factory()->create();
        MemberProfile::create($this->memberData($state, $district, $visibleUser, [
            'full_name' => 'Visible Member',
            'profile_photo' => 'profile_photos/visible.jpg',
        ]));
        UserPrivacySetting::create(['user_id' => $visibleUser->id, 'photo_visibility' => 'hidden']);

        $hiddenUser = User::factory()->create();
        MemberProfile::create($this->memberData($state, $district, $hiddenUser, [
            'full_name' => 'Hidden Member',
            'mobile' => '9000000099',
        ]));
        UserPrivacySetting::create(['user_id' => $hiddenUser->id, 'public_profile_visible' => false]);

        $this->get(route('directory'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Directory')
                ->has('members.data', 1)
                ->where('members.data.0.full_name', 'Visible Member')
                ->where('members.data.0.profile_photo', null)
                ->missing('members.data.0.mobile')
                ->missing('members.data.0.email')
                ->missing('members.data.0.current_address')
                ->missing('members.data.0.dob'));
    }

    public function test_matrimonial_directory_hides_contact_details_and_hidden_photos(): void
    {
        $owner = User::factory()->create();
        MatrimonialProfile::create($this->matrimonialData([
            'user_id' => $owner->id,
            'status' => 'approved',
            'photo' => 'matrimonial_photos/a.jpg',
            'id_proof' => 'matrimonial_ids/a.pdf',
        ]));
        UserPrivacySetting::create(['user_id' => $owner->id, 'matrimonial_photo_visibility' => 'hidden']);

        $this->actingAs(User::factory()->create())
            ->get(route('matrimonial-directory.index', ['min_age' => 'abc', 'max_age' => 'xyz']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('MatrimonialDirectory')
                ->has('profiles.data', 1)
                ->where('profiles.data.0.photo_url', null)
                ->missing('profiles.data.0.mobile')
                ->missing('profiles.data.0.email')
                ->missing('profiles.data.0.id_proof')
                ->missing('profiles.data.0.user'));
    }

    public function test_members_without_email_can_log_in_with_mobile_number(): void
    {
        User::create([
            'name' => 'Mobile Only',
            'mobile' => '9876543210',
            'password' => Hash::make('secret-pass'),
            'status' => 'approved',
        ]);

        $this->post('/login', [
            'email' => '+91 98765 43210',
            'password' => 'secret-pass',
        ])->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    public function test_otp_request_does_not_create_accounts_and_verify_is_throttled(): void
    {
        $this->post(route('login.otp.generate'), ['mobile' => '9123456789'])
            ->assertSessionHasErrors('mobile');
        $this->assertDatabaseMissing('users', ['mobile' => '9123456789']);

        $user = User::factory()->create(['mobile' => '9123456780']);
        $this->post(route('login.otp.generate'), ['mobile' => '9123456780'])
            ->assertSessionHasNoErrors();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.otp.verify'), ['mobile' => '9123456780', 'otp' => '000000'])
                ->assertSessionHasErrors('otp');
        }

        // Even the correct code is refused once the attempt budget is spent.
        $this->post(route('login.otp.verify'), ['mobile' => '9123456780', 'otp' => '123456'])
            ->assertSessionHasErrors('otp');
        $this->assertGuest();
        $this->assertNull($user->fresh()->otp_code);
    }

    public function test_community_profile_rejects_mobile_used_by_another_account_and_keeps_rejection(): void
    {
        [$state, $district] = $this->location();
        User::factory()->create(['mobile' => '9000000077']);

        $user = User::factory()->create(['mobile' => '9000000066']);
        MemberProfile::create($this->memberData($state, $district, $user, ['status' => 'rejected', 'mobile' => '9000000066']));

        $payload = [
            'full_name' => 'Updated Name',
            'gender' => 'male',
            'mobile' => '9000000077',
        ];

        $this->actingAs($user)
            ->post(route('user.member-profile.update'), $payload)
            ->assertSessionHasErrors('mobile');

        $this->actingAs($user)
            ->post(route('user.member-profile.update'), ['mobile' => '9000000066'] + $payload)
            ->assertSessionHasNoErrors();

        $this->assertSame('rejected', $user->memberProfile()->first()->status);
    }

    public function test_exports_headings_line_up_with_values(): void
    {
        [$state, $district] = $this->location();
        MemberProfile::create($this->memberData($state, $district, null, ['admin_note' => 'note']));
        MatrimonialProfile::create($this->matrimonialData());
        ContactEnquiry::create([
            'name' => 'Asker',
            'mobile' => '9000000010',
            'message' => 'The message',
            'purpose' => 'The purpose',
        ]);

        foreach ([new MembersExport, new MatrimonialExport, new ContactExport] as $export) {
            $row = $export->map($export->collection()->first());
            $this->assertCount(count($export->headings()), $row, $export::class);
        }

        $members = new MembersExport;
        $memberRow = array_combine($members->headings(), $members->map($members->collection()->first()));
        $this->assertSame('Bihar', $memberRow['State']);
        $this->assertSame('note', $memberRow['Admin Note']);

        $contacts = new ContactExport;
        $contactRow = array_combine($contacts->headings(), $contacts->map($contacts->collection()->first()));
        $this->assertSame('The purpose', $contactRow['Purpose']);
        $this->assertSame('The message', $contactRow['Message']);
    }

    public function test_every_page_shares_version_and_last_updated_info(): void
    {
        config(['app.version' => '2.4.1', 'app.updated_at' => '2026-11-09 18:30']);
        \App\Support\AppVersion::flush();

        $this->get(route('welcome'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('appVersion.version', '2.4.1')
                ->where('appVersion.updated_at_display', '09 Nov 2026, 06:30 PM IST'));

        $this->actingAs($this->admin())
            ->get(route('admin.matrimonial'))
            ->assertInertia(fn (Assert $page) => $page->where('appVersion.version', '2.4.1'));

        \App\Support\AppVersion::flush();
    }

    public function test_help_location_filter_matches_state_names(): void
    {
        \App\Models\HelpRequest::create([
            'name' => 'Seeker', 'mobile' => '9000000003', 'state' => 'Bihar', 'district' => 'Patna',
            'city' => 'Danapur', 'category' => 'Education Help', 'title' => 'Books', 'description' => 'Need books',
            'urgency' => 'Normal', 'is_public' => true, 'status' => 'Approved',
        ]);

        $this->get(route('help.index', ['city' => 'Bihar']))
            ->assertInertia(fn (Assert $page) => $page->has('requests.data', 1));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'approved']);
    }

    /**
     * @return array{0: State, 1: District}
     */
    private function location(): array
    {
        $state = State::create(['name_en' => 'Bihar', 'name_hi' => 'Bihar', 'code' => 'BR', 'status' => true]);
        $district = District::create(['state_id' => $state->id, 'name_en' => 'Patna', 'name_hi' => 'Patna', 'status' => true]);

        return [$state, $district];
    }

    private function memberData(State $state, District $district, ?User $user = null, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user?->id,
            'full_name' => 'Test Member',
            'gender' => 'male',
            'mobile' => '9000000001',
            'email' => 'member@example.com',
            'dob' => '1990-01-01',
            'current_address' => 'House 1, Patna',
            'state_id' => $state->id,
            'district_id' => $district->id,
            'city_village' => 'Patna',
            'is_public' => true,
            'status' => 'approved',
        ], $overrides);
    }

    private function matrimonialData(array $overrides = []): array
    {
        return array_merge([
            'profile_for' => 'Self',
            'full_name' => 'Test Matrimonial',
            'gender' => 'female',
            'dob' => '1998-01-01',
            'marital_status' => 'Never Married',
            'mobile' => '9000000002',
            'email' => 'bride@example.com',
            'status' => 'pending',
        ], $overrides);
    }
}
