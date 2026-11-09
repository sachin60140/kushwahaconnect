<?php

namespace Tests\Feature;

use App\Models\HelpRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AdminHelpRequestCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_the_create_form(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.help-requests.create'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/HelpRequests/Create')
                ->where('categories', HelpRequest::CATEGORIES)
                ->where('statuses', HelpRequest::STATUSES));
    }

    public function test_admin_created_request_goes_live_and_is_shareable(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.help-requests.store'), $this->payload())
            ->assertRedirect(route('admin.help-requests.index'))
            ->assertSessionHas('success', 'Help request created. It is now live on the Help page and can be shared.');

        $help = HelpRequest::firstOrFail();
        $this->assertSame('Ramesh Kushwaha', $help->name);
        $this->assertSame('Approved', $help->status);
        $this->assertTrue($help->is_public);
        $this->assertNull($help->user_id, 'Created on behalf of someone else, not linked to the admin account.');

        $this->get(route('help.index'))
            ->assertInertia(fn (Assert $page) => $page->where('requests.data.0.title', 'Help for flood-affected family'));
        $this->get(route('help.show', $help->id))->assertOk();
    }

    public function test_private_or_pending_admin_request_is_saved_but_not_public(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.help-requests.store'), $this->payload(['is_public' => false]))
            ->assertSessionHas('success', 'Help request created. It is not public yet (private or not approved).');

        $this->actingAs($this->admin())
            ->post(route('admin.help-requests.store'), $this->payload(['status' => 'Pending', 'title' => 'Pending one']));

        $this->assertSame(2, HelpRequest::count());
        foreach (HelpRequest::all() as $help) {
            $this->get(route('help.show', $help->id))->assertNotFound();
        }
    }

    public function test_validation_rejects_missing_and_unknown_values(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.help-requests.store'), $this->payload([
                'name' => '',
                'mobile' => '',
                'category' => 'Not a category',
                'urgency' => 'Whenever',
                'status' => 'Live',
            ]))
            ->assertSessionHasErrors(['name', 'mobile', 'category', 'urgency', 'status']);

        $this->assertSame(0, HelpRequest::count());
    }

    public function test_non_admins_and_guests_cannot_create_requests_here(): void
    {
        $member = User::factory()->create(['role' => 'user']);

        $this->actingAs($member)->get(route('admin.help-requests.create'))->assertRedirect(route('welcome'));
        $this->actingAs($member)->post(route('admin.help-requests.store'), $this->payload())->assertRedirect(route('welcome'));

        auth()->logout();
        $this->get(route('admin.help-requests.create'))->assertRedirect(route('login'));

        $this->assertSame(0, HelpRequest::count());
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'approved']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Ramesh Kushwaha',
            'mobile' => '9123456789',
            'whatsapp' => '9123456789',
            'state' => 'Bihar',
            'district' => 'Darbhanga',
            'city' => 'Darbhanga',
            'category' => 'Emergency Help',
            'title' => 'Help for flood-affected family',
            'description' => 'House damaged in floods; family needs food, clothes and temporary shelter.',
            'urgency' => 'Emergency',
            'status' => 'Approved',
            'is_public' => true,
        ], $overrides);
    }
}
