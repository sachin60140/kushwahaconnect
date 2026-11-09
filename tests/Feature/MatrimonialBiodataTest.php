<?php

namespace Tests\Feature;

use App\Models\Connection;
use App\Models\MatrimonialProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MatrimonialBiodataTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_public_registration_requires_complete_key_details(): void
    {
        $this->post(route('matrimonial.store'), [
            'profile_for' => 'Self',
            'full_name' => 'Priya Kumari',
            'gender' => 'Female',
            'dob' => '1998-05-14',
            'marital_status' => 'Never Married',
            'mobile' => '9811111111',
            'consent' => true,
        ])->assertSessionHasErrors(['height', 'education', 'occupation', 'father_name', 'family_location']);

        $this->post(route('matrimonial.store'), $this->details(['consent' => true]))
            ->assertSessionHasNoErrors();

        $profile = MatrimonialProfile::firstOrFail();
        $this->assertSame('5 ft 3 in', $profile->height);
        $this->assertSame('Ram Prasad', $profile->father_name);
        $this->assertSame('26-32', $profile->pref_age_range);
        $this->assertSame('pending', $profile->status);
    }

    public function test_new_member_profile_requires_a_biodata(): void
    {
        $user = User::factory()->create(['mobile' => '9811111111']);

        $this->actingAs($user)
            ->post(route('user.matrimonial-profile.update'), $this->details())
            ->assertSessionHasErrors('biodata');
        $this->assertSame(0, MatrimonialProfile::count());

        $this->actingAs($user)
            ->post(route('user.matrimonial-profile.update'), $this->details([
                'biodata' => UploadedFile::fake()->create('biodata.pdf', 300, 'application/pdf'),
                'photo' => UploadedFile::fake()->image('photo.jpg', 600, 800),
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $profile = MatrimonialProfile::where('user_id', $user->id)->firstOrFail();
        $this->assertStringStartsWith('matrimonial_biodata/', $profile->biodata);
        Storage::disk('local')->assertExists($profile->biodata);
        Storage::disk('local')->assertExists($profile->photo);
    }

    public function test_existing_profile_without_biodata_is_not_blocked_and_can_replace_later(): void
    {
        $user = User::factory()->create(['mobile' => '9811111111']);
        $profile = MatrimonialProfile::create($this->details(['user_id' => $user->id, 'status' => 'approved']));

        $this->actingAs($user)
            ->post(route('user.matrimonial-profile.update'), $this->details(['occupation' => 'Lecturer']))
            ->assertSessionHasNoErrors();
        $this->assertSame('Lecturer', $profile->fresh()->occupation);

        $this->actingAs($user)->post(route('user.matrimonial-profile.update'), $this->details([
            'biodata' => UploadedFile::fake()->create('first.pdf', 100, 'application/pdf'),
        ]));
        $first = $profile->fresh()->biodata;

        $this->actingAs($user)->post(route('user.matrimonial-profile.update'), $this->details([
            'biodata' => UploadedFile::fake()->image('second.png'),
        ]));
        $second = $profile->fresh()->biodata;

        $this->assertNotSame($first, $second);
        Storage::disk('local')->assertMissing($first);
        Storage::disk('local')->assertExists($second);
    }

    public function test_biodata_file_type_and_size_are_limited(): void
    {
        $user = User::factory()->create(['mobile' => '9811111111']);

        $this->actingAs($user)->post(route('user.matrimonial-profile.update'), $this->details([
            'biodata' => UploadedFile::fake()->create('biodata.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
        ]))->assertSessionHasErrors('biodata');

        $this->actingAs($user)->post(route('user.matrimonial-profile.update'), $this->details([
            'biodata' => UploadedFile::fake()->create('big.pdf', 6000, 'application/pdf'),
        ]))->assertSessionHasErrors('biodata');
    }

    public function test_only_owner_admin_or_accepted_interest_can_open_a_biodata(): void
    {
        [$owner, $profile] = $this->profileWithBiodata();
        $admin = User::factory()->create(['role' => 'admin']);
        $stranger = User::factory()->create();
        $accepted = User::factory()->create();
        $pending = User::factory()->create();
        Connection::create(['sender_id' => $accepted->id, 'receiver_id' => $owner->id, 'status' => 'accepted']);
        Connection::create(['sender_id' => $pending->id, 'receiver_id' => $owner->id, 'status' => 'pending']);

        $url = route('matrimonial.biodata', $profile->id);

        $this->actingAs($owner)->get($url)->assertOk();
        $this->actingAs($admin)->get($url)->assertOk();
        $this->actingAs($accepted)->get($url)->assertOk();
        $this->actingAs($stranger)->get($url)->assertNotFound();
        $this->actingAs($pending)->get($url)->assertNotFound();

        auth()->logout();
        $this->get($url)->assertRedirect(route('login'));
    }

    public function test_directory_hides_biodata_path_and_links_it_only_after_accepted_interest(): void
    {
        [$owner, $profile] = $this->profileWithBiodata();
        $stranger = User::factory()->create();
        $accepted = User::factory()->create();
        Connection::create(['sender_id' => $owner->id, 'receiver_id' => $accepted->id, 'status' => 'accepted']);

        $this->actingAs($stranger)->get(route('matrimonial-directory.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('profiles.data.0.biodata')
                ->where('profiles.data.0.biodata_url', null));

        $this->actingAs($accepted)->get(route('matrimonial-directory.index'))
            ->assertInertia(fn (Assert $page) => $page
                ->missing('profiles.data.0.biodata')
                ->where('profiles.data.0.biodata_url', route('matrimonial.biodata', $profile->id)));
    }

    public function test_member_page_does_not_expose_file_paths(): void
    {
        [$owner, $profile] = $this->profileWithBiodata();

        $this->actingAs($owner)->get(route('user.matrimonial-profile.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->component('User/MatrimonialProfile/Edit')
                ->where('isNew', false)
                ->where('biodataUrl', route('matrimonial.biodata', $profile->id))
                ->missing('profile.biodata')
                ->missing('profile.id_proof')
                ->missing('profile.photo'));

        $this->actingAs(User::factory()->create())->get(route('user.matrimonial-profile.edit'))
            ->assertInertia(fn (Assert $page) => $page->where('isNew', true)->where('biodataUrl', null));
    }

    public function test_admin_can_upload_biodata_on_behalf_and_delete_removes_it(): void
    {
        $guestProfile = MatrimonialProfile::create($this->details(['status' => 'pending']));
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->post(route('admin.matrimonial.biodata', $guestProfile->id), ['biodata' => UploadedFile::fake()->create('b.pdf', 50, 'application/pdf')])
            ->assertRedirect(route('welcome'));
        $this->assertNull($guestProfile->fresh()->biodata);

        $this->actingAs($admin)
            ->from(route('admin.matrimonial'))
            ->post(route('admin.matrimonial.biodata', $guestProfile->id), ['biodata' => UploadedFile::fake()->create('b.pdf', 50, 'application/pdf')])
            ->assertRedirect(route('admin.matrimonial'))
            ->assertSessionHas('success', 'Biodata uploaded.');

        $path = $guestProfile->fresh()->biodata;
        Storage::disk('local')->assertExists($path);

        $this->actingAs($admin)->get(route('admin.matrimonial'))
            ->assertInertia(fn (Assert $page) => $page->where('profiles.data.0.biodata_url', route('matrimonial.biodata', $guestProfile->id)));

        $this->actingAs($admin)->delete(route('admin.matrimonial.destroy', $guestProfile->id));
        Storage::disk('local')->assertMissing($path);
    }

    /**
     * @return array{0: User, 1: MatrimonialProfile}
     */
    private function profileWithBiodata(): array
    {
        $owner = User::factory()->create(['mobile' => '9811111111']);
        $profile = MatrimonialProfile::create($this->details(['user_id' => $owner->id, 'status' => 'approved']));
        $profile->replaceBiodata(UploadedFile::fake()->create('biodata.pdf', 100, 'application/pdf'));

        return [$owner, $profile];
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'profile_for' => 'Self',
            'full_name' => 'Priya Kumari',
            'gender' => 'Female',
            'dob' => '1998-05-14',
            'marital_status' => 'Never Married',
            'height' => '5 ft 3 in',
            'manglik' => 'No',
            'gotra' => 'Kashyap',
            'current_city' => 'Patna',
            'education' => 'M.Sc.',
            'occupation' => 'Teacher',
            'father_name' => 'Ram Prasad',
            'family_location' => 'Gaya',
            'brothers' => 1,
            'sisters' => 1,
            'pref_age_range' => '26-32',
            'mobile' => '9811111111',
        ], $overrides);
    }
}
