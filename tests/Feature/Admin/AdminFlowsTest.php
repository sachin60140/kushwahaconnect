<?php

namespace Tests\Feature\Admin;

use App\Models\BusinessListing;
use App\Models\ContactEnquiry;
use App\Models\District;
use App\Models\EducationResource;
use App\Models\Gallery;
use App\Models\HelpRequest;
use App\Models\JobPosting;
use App\Models\MatrimonialProfile;
use App\Models\MedicalResource;
use App\Models\MemberProfile;
use App\Models\NewsEvent;
use App\Models\State;
use App\Models\User;
use App\Models\Volunteer;
use App\Models\WebsiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_users_cannot_access_admin_area(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('admin.members'))
            ->assertRedirect(route('welcome'));
    }

    public function test_admin_pages_render_successfully(): void
    {
        $this->actingAs($this->admin());

        $state = $this->state();
        $district = $this->district($state);
        $news = NewsEvent::create($this->newsData());
        $education = EducationResource::create($this->educationData());
        $medical = MedicalResource::create($this->medicalData());

        MemberProfile::create($this->memberData($state, $district));
        MatrimonialProfile::create($this->matrimonialData());
        ContactEnquiry::create($this->enquiryData($state, $district));
        Gallery::create(['title' => 'Samaj Event', 'description' => 'Gallery details']);
        BusinessListing::create($this->businessData($state, $district, User::factory()->create()));

        $routes = [
            route('dashboard'),
            route('admin.members'),
            route('admin.matrimonial'),
            route('admin.enquiries'),
            route('admin.news.index'),
            route('admin.news.create'),
            route('admin.news.edit', $news),
            route('admin.jobs.index'),
            route('admin.education.index'),
            route('admin.education.create'),
            route('admin.education.edit', $education),
            route('admin.medical.index'),
            route('admin.medical.create'),
            route('admin.medical.edit', $medical),
            route('admin.help-requests.index'),
            route('admin.volunteers.index'),
            route('admin.gallery.index'),
            route('admin.gallery.create'),
            route('admin.settings.index'),
            route('admin.business.index'),
        ];

        foreach ($routes as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_admin_can_update_review_statuses(): void
    {
        Notification::fake();

        $this->actingAs($this->admin());

        $state = $this->state();
        $district = $this->district($state);
        $memberUser = User::factory()->create(['status' => 'pending_approval']);
        $member = MemberProfile::create($this->memberData($state, $district, ['user_id' => $memberUser->id]));
        $matrimonial = MatrimonialProfile::create($this->matrimonialData());
        $job = JobPosting::create($this->jobData(User::factory()->create()));
        $help = HelpRequest::create($this->helpData());
        $volunteer = Volunteer::create($this->volunteerData());
        $business = BusinessListing::create($this->businessData($state, $district, User::factory()->create()));

        $this->patch(route('admin.members.status', $member), ['status' => 'approved'])->assertRedirect();
        $this->patch(route('admin.matrimonial.status', $matrimonial), ['status' => 'approved'])->assertRedirect();
        $this->patch(route('admin.jobs.status', $job), ['status' => 'closed'])->assertRedirect();
        $this->patch(route('admin.help-requests.status', $help), ['status' => 'Resolved'])->assertRedirect();
        $this->patch(route('admin.volunteers.status', $volunteer), ['status' => 'Approved'])->assertRedirect();
        $this->patch(route('admin.business.update-status', $business), ['status' => 'approved'])->assertRedirect();

        $this->assertDatabaseHas('member_profiles', ['id' => $member->id, 'status' => 'approved']);
        $this->assertDatabaseHas('users', ['id' => $memberUser->id, 'status' => 'approved']);
        $this->assertDatabaseHas('matrimonial_profiles', ['id' => $matrimonial->id, 'status' => 'approved']);
        $this->assertDatabaseHas('job_postings', ['id' => $job->id, 'status' => 'closed']);
        $this->assertDatabaseHas('help_requests', ['id' => $help->id, 'status' => 'Resolved']);
        $this->assertDatabaseHas('volunteers', ['id' => $volunteer->id, 'status' => 'Approved']);
        $this->assertDatabaseHas('business_listings', ['id' => $business->id, 'status' => 'approved']);
    }

    public function test_admin_content_crud_and_settings_work(): void
    {
        $this->actingAs($this->admin());
        Storage::fake('public');

        $this->post(route('admin.news.store'), $this->newsData(['title' => 'Community Update']))
            ->assertRedirect(route('admin.news.index'));

        $news = NewsEvent::where('title', 'Community Update')->firstOrFail();

        $this->put(route('admin.news.update', $news), $this->newsData([
            'title' => 'Updated Community Update',
            'type' => 'event',
            'event_date' => now()->addWeek()->format('Y-m-d H:i:s'),
            'event_location' => 'Patna',
            'registration_url' => 'https://example.com/register',
        ]))->assertRedirect(route('admin.news.index'));

        $this->assertDatabaseHas('news_events', [
            'id' => $news->id,
            'title' => 'Updated Community Update',
            'type' => 'event',
        ]);

        $this->post(route('admin.education.store'), $this->educationData(['title' => 'Scholarship Alert']))
            ->assertRedirect(route('admin.education.index'));

        $education = EducationResource::where('title', 'Scholarship Alert')->firstOrFail();

        $this->put(route('admin.education.update', $education), $this->educationData(['title' => 'Updated Scholarship Alert']))
            ->assertRedirect(route('admin.education.index'));

        $this->post(route('admin.medical.store'), $this->medicalData(['title' => 'Doctor Directory']))
            ->assertRedirect(route('admin.medical.index'));

        $medical = MedicalResource::where('title', 'Doctor Directory')->firstOrFail();

        $this->put(route('admin.medical.update', $medical), $this->medicalData(['title' => 'Updated Doctor Directory']))
            ->assertRedirect(route('admin.medical.index'));

        $this->post(route('admin.gallery.store'), [
            'title' => 'Community Gallery',
            'description' => 'Event photos',
            'is_published' => true,
        ])->assertRedirect(route('admin.gallery.index'));

        $this->post(route('admin.settings.update'), [
            'contact_email' => 'admin@example.com',
            'announcement_en' => 'Welcome to KushwahaConnect',
            'founder_photo_upload' => UploadedFile::fake()->image('founder.jpg', 300, 300),
        ])->assertRedirect();

        $this->assertDatabaseHas('education_resources', ['id' => $education->id, 'title' => 'Updated Scholarship Alert']);
        $this->assertDatabaseHas('medical_resources', ['id' => $medical->id, 'title' => 'Updated Doctor Directory']);
        $this->assertDatabaseHas('galleries', ['title' => 'Community Gallery']);
        $this->assertDatabaseHas('website_settings', ['key' => 'contact_email', 'value' => 'admin@example.com']);

        $founderPhotoUrl = WebsiteSetting::where('key', 'founder_photo_url')->value('value');

        $this->assertNotEmpty($founderPhotoUrl);
        $this->assertStringStartsWith('/public-uploads/founder/', $founderPhotoUrl);
        Storage::disk('public')->assertExists(Str::after($founderPhotoUrl, '/public-uploads/'));
    }

    public function test_admin_delete_actions_work(): void
    {
        $this->actingAs($this->admin());

        $state = $this->state();
        $district = $this->district($state);

        $member = MemberProfile::create($this->memberData($state, $district));
        $matrimonial = MatrimonialProfile::create($this->matrimonialData());
        $job = JobPosting::create($this->jobData(User::factory()->create()));
        $help = HelpRequest::create($this->helpData());
        $volunteer = Volunteer::create($this->volunteerData());
        $business = BusinessListing::create($this->businessData($state, $district, User::factory()->create()));
        $news = NewsEvent::create($this->newsData());
        $education = EducationResource::create($this->educationData());
        $medical = MedicalResource::create($this->medicalData());
        $gallery = Gallery::create(['title' => 'Delete Gallery']);

        $this->delete(route('admin.members.destroy', $member))->assertRedirect();
        $this->delete(route('admin.matrimonial.destroy', $matrimonial))->assertRedirect();
        $this->delete(route('admin.jobs.destroy', $job))->assertRedirect();
        $this->delete(route('admin.help-requests.destroy', $help))->assertRedirect();
        $this->delete(route('admin.volunteers.destroy', $volunteer))->assertRedirect();
        $this->delete(route('admin.business.destroy', $business))->assertRedirect();
        $this->delete(route('admin.news.destroy', $news))->assertRedirect(route('admin.news.index'));
        $this->delete(route('admin.education.destroy', $education))->assertRedirect(route('admin.education.index'));
        $this->delete(route('admin.medical.destroy', $medical))->assertRedirect(route('admin.medical.index'));
        $this->delete(route('admin.gallery.destroy', $gallery))->assertRedirect(route('admin.gallery.index'));

        $this->assertDatabaseMissing('member_profiles', ['id' => $member->id]);
        $this->assertDatabaseMissing('matrimonial_profiles', ['id' => $matrimonial->id]);
        $this->assertDatabaseMissing('job_postings', ['id' => $job->id]);
        $this->assertDatabaseMissing('help_requests', ['id' => $help->id]);
        $this->assertDatabaseMissing('volunteers', ['id' => $volunteer->id]);
        $this->assertDatabaseMissing('business_listings', ['id' => $business->id]);
        $this->assertDatabaseMissing('news_events', ['id' => $news->id]);
        $this->assertDatabaseMissing('education_resources', ['id' => $education->id]);
        $this->assertDatabaseMissing('medical_resources', ['id' => $medical->id]);
        $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
    }

    public function test_business_admin_search_keeps_status_filter_grouped(): void
    {
        $this->actingAs($this->admin());

        $state = $this->state();
        $district = $this->district($state);
        $owner = User::factory()->create(['name' => 'Matching Owner']);

        BusinessListing::create($this->businessData($state, $district, $owner, [
            'business_name' => 'Approved Match',
            'status' => 'approved',
        ]));

        BusinessListing::create($this->businessData($state, $district, $owner, [
            'business_name' => 'Rejected Match',
            'status' => 'rejected',
        ]));

        $response = $this->get(route('admin.business.index', [
            'search' => 'Matching Owner',
            'status' => 'approved',
        ]));

        $response->assertOk();

        $businesses = $response->viewData('page')['props']['businesses']['data'];

        $this->assertCount(1, $businesses);
        $this->assertSame('approved', $businesses[0]['status']);
        $this->assertSame('Approved Match', $businesses[0]['business_name']);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);
    }

    private function state(): State
    {
        return State::create([
            'name_en' => 'Bihar',
            'name_hi' => 'Bihar',
            'code' => 'BR',
            'status' => true,
        ]);
    }

    private function district(State $state): District
    {
        return District::create([
            'state_id' => $state->id,
            'name_en' => 'Patna',
            'name_hi' => 'Patna',
            'status' => true,
        ]);
    }

    private function memberData(State $state, District $district, array $overrides = []): array
    {
        return array_merge([
            'full_name' => 'Test Member',
            'gender' => 'male',
            'mobile' => '9000000001',
            'email' => 'member@example.com',
            'state_id' => $state->id,
            'district_id' => $district->id,
            'city_village' => 'Patna',
            'status' => 'pending',
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
            'status' => 'pending',
        ], $overrides);
    }

    private function jobData(User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'title' => 'Developer',
            'company' => 'KC Tech',
            'location' => 'Patna',
            'description' => 'Build community tools',
            'contact_email' => 'jobs@example.com',
            'status' => 'pending',
        ], $overrides);
    }

    private function helpData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Help Seeker',
            'mobile' => '9000000003',
            'state' => 'Bihar',
            'district' => 'Patna',
            'city' => 'Patna',
            'category' => 'Education Help',
            'title' => 'Need books',
            'description' => 'Student needs books',
            'urgency' => 'Normal',
            'status' => 'Pending',
            'is_public' => true,
        ], $overrides);
    }

    private function volunteerData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Volunteer',
            'mobile' => '9000000004',
            'state' => 'Bihar',
            'district' => 'Patna',
            'city' => 'Patna',
            'help_areas' => ['Education'],
            'status' => 'Pending',
        ], $overrides);
    }

    private function businessData(State $state, District $district, User $user, array $overrides = []): array
    {
        return array_merge([
            'user_id' => $user->id,
            'business_name' => 'KC Business',
            'category' => 'Services',
            'description' => 'Community business',
            'address' => 'Main Road',
            'city' => 'Patna',
            'state_id' => $state->id,
            'district_id' => $district->id,
            'pincode' => '800001',
            'contact_number' => '9000000005',
            'status' => 'pending',
        ], $overrides);
    }

    private function newsData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Community News',
            'content' => 'News content',
            'type' => 'news',
            'event_date' => null,
            'event_location' => null,
            'registration_url' => null,
            'is_published' => true,
        ], $overrides);
    }

    private function educationData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Education Resource',
            'category' => 'Scholarship',
            'description' => 'Scholarship details',
            'link_url' => 'https://example.com/education',
            'deadline_date' => now()->addMonth()->toDateString(),
            'is_published' => true,
        ], $overrides);
    }

    private function medicalData(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Medical Resource',
            'type' => 'doctor',
            'description' => 'Doctor details',
            'contact_info' => '9000000006',
            'is_published' => true,
        ], $overrides);
    }

    private function enquiryData(State $state, District $district, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Contact User',
            'mobile' => '9000000007',
            'email' => 'contact@example.com',
            'state_id' => $state->id,
            'district_id' => $district->id,
            'message' => 'Please contact me',
            'purpose' => 'General',
        ], $overrides);
    }
}
