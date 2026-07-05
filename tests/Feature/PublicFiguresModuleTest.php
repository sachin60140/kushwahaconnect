<?php

namespace Tests\Feature;

use App\Models\KushwahaPrideProfile;
use App\Models\PersonalityNomination;
use App\Models\ProfileCorrectionRequest;
use App\Models\PublicRepresentative;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicFiguresModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_only_show_published_non_archived_profiles(): void
    {
        KushwahaPrideProfile::create($this->prideData(['name_en' => 'Published Personality', 'is_published' => true]));
        KushwahaPrideProfile::create($this->prideData(['name_en' => 'Draft Personality', 'is_published' => false]));

        PublicRepresentative::create($this->representativeData(['name' => 'Published Representative', 'is_published' => true]));
        PublicRepresentative::create($this->representativeData(['name' => 'Archived Representative', 'is_published' => true, 'archived_at' => now()]));

        $this->get(route('pride.index'))
            ->assertOk()
            ->assertSee('Published Personality')
            ->assertDontSee('Draft Personality');

        $this->get(route('representatives.index'))
            ->assertOk()
            ->assertSee('Published Representative')
            ->assertDontSee('Archived Representative');
    }

    public function test_public_representative_photo_uses_public_upload_route(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('public-representatives/rep.jpg', 'representative photo');

        PublicRepresentative::create($this->representativeData([
            'name' => 'Representative With Photo',
            'photo_path' => 'public-representatives/rep.jpg',
            'is_published' => true,
        ]));

        $this->get(route('representatives.index'))
            ->assertOk()
            ->assertSee('\/public-uploads\/public-representatives\/rep.jpg', false)
            ->assertDontSee('/storage/public-representatives/rep.jpg', false);

        $this->get(route('public.uploads.show', ['path' => 'public-representatives/rep.jpg']))
            ->assertOk();
    }

    public function test_admin_representative_photo_upload_is_resized_with_required_aspect_ratio(): void
    {
        Storage::fake('public');

        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.representatives.store'), [
                'name' => 'Auto Sized Representative',
                'photo' => UploadedFile::fake()->image('wide-photo.jpg', 2400, 900),
                'representative_status' => 'current',
                'category' => 'MLA',
                'current_designation' => 'MLA',
                'political_party' => 'Public Party',
                'constituency' => 'Patna',
                'state' => 'Bihar',
                'district' => 'Patna',
                'is_verified' => true,
                'is_published' => true,
            ])
            ->assertRedirect(route('admin.representatives.index'));

        $representative = PublicRepresentative::where('name', 'Auto Sized Representative')->firstOrFail();

        Storage::disk('public')->assertExists($representative->photo_path);

        $this->assertStringStartsWith('public-representatives/', $representative->photo_path);
        $this->assertStringEndsWith('.jpg', $representative->photo_path);
        $this->assertSame([900, 1200], array_slice(getimagesize(Storage::disk('public')->path($representative->photo_path)), 0, 2));
    }

    public function test_visitors_can_submit_nominations_and_correction_requests(): void
    {
        $this->post(route('nominations.store'), [
            'person_name' => 'Inspiring Person',
            'category' => 'Education',
            'profession_or_designation' => 'Teacher',
            'state' => 'Bihar',
            'district' => 'Patna',
            'achievement' => 'Created a scholarship support network.',
            'biography' => 'Community educator.',
            'source_links' => 'https://example.com/source',
            'social_links' => 'https://example.com/profile',
            'nominator_name' => 'Community Member',
            'nominator_mobile' => '9000000000',
            'relationship' => 'Student',
            'consent' => true,
        ])->assertRedirect();

        $this->assertDatabaseHas('personality_nominations', [
            'person_name' => 'Inspiring Person',
            'status' => 'submitted',
        ]);

        $this->post(route('corrections.store'), [
            'profile_type' => 'personality',
            'profile_name' => 'Inspiring Person',
            'requester_name' => 'Verifier',
            'requester_email' => 'verifier@example.com',
            'correction_details' => 'Please update the designation.',
            'source_links' => 'https://example.com/correction',
        ])->assertRedirect();

        $this->assertDatabaseHas('profile_correction_requests', [
            'profile_type' => 'personality',
            'profile_name' => 'Inspiring Person',
            'status' => 'submitted',
        ]);

        $this->assertSame(1, PersonalityNomination::count());
        $this->assertSame(1, ProfileCorrectionRequest::count());
    }

    public function test_admin_can_render_and_review_new_module_pages(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);

        $this->actingAs($admin);

        $profile = KushwahaPrideProfile::create($this->prideData());
        $representative = PublicRepresentative::create($this->representativeData());
        $nomination = PersonalityNomination::create([
            'person_name' => 'Pending Nomination',
            'category' => 'Education',
            'achievement' => 'Community achievement',
            'nominator_name' => 'Nominator',
            'nominator_mobile' => '9000000000',
            'consent' => true,
            'status' => 'submitted',
        ]);
        $correction = ProfileCorrectionRequest::create([
            'profile_type' => 'representative',
            'profile_name' => 'Representative',
            'requester_name' => 'Requester',
            'correction_details' => 'Update term dates.',
            'status' => 'submitted',
        ]);

        $this->get(route('admin.pride.index'))->assertOk();
        $this->get(route('admin.pride.create'))->assertOk();
        $this->get(route('admin.pride.edit', $profile))->assertOk();
        $this->get(route('admin.representatives.index'))->assertOk();
        $this->get(route('admin.representatives.create'))->assertOk();
        $this->get(route('admin.representatives.edit', $representative))->assertOk();
        $this->get(route('admin.nominations.index'))->assertOk();
        $this->get(route('admin.corrections.index'))->assertOk();

        $this->patch(route('admin.nominations.update', $nomination), [
            'status' => 'under_review',
            'admin_note' => 'Checking sources.',
        ])->assertRedirect();

        $this->patch(route('admin.corrections.update', $correction), [
            'status' => 'resolved',
            'admin_note' => 'Updated after verification.',
        ])->assertRedirect();

        $this->assertDatabaseHas('personality_nominations', ['id' => $nomination->id, 'status' => 'under_review']);
        $this->assertDatabaseHas('profile_correction_requests', ['id' => $correction->id, 'status' => 'resolved']);
    }

    private function prideData(array $overrides = []): array
    {
        return array_merge([
            'name_hi' => 'प्रेरक व्यक्ति',
            'name_en' => 'Inspiring Personality',
            'category' => 'Education',
            'designation_hi' => 'शिक्षक',
            'designation_en' => 'Teacher',
            'state' => 'Bihar',
            'district' => 'Patna',
            'is_verified' => true,
            'is_published' => true,
        ], $overrides);
    }

    private function representativeData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Public Representative',
            'representative_status' => 'current',
            'category' => 'MLA',
            'current_designation' => 'MLA',
            'political_party' => 'Public Party',
            'constituency' => 'Patna',
            'state' => 'Bihar',
            'district' => 'Patna',
            'is_verified' => true,
            'is_published' => true,
        ], $overrides);
    }
}
