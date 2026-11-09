<?php

namespace Tests\Feature;

use App\Models\HelpRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class HelpRequestShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_help_request_has_its_own_shareable_page_with_preview_tags(): void
    {
        $help = HelpRequest::create($this->helpData());

        $response = $this->get(route('help.show', $help->id));

        $response->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Help/Show')
                ->where('helpRequest.id', $help->id)
                ->where('helpRequest.title', 'Stage 4 cancer treatment support')
                ->missing('helpRequest.user_id')
                ->where('seo.type', 'article'));

        // Server-rendered social preview tags (what Facebook/WhatsApp read).
        $response->assertSee('<meta property="og:title" content="Stage 4 cancer treatment support - Help Request', false);
        $response->assertSee('Emergency Emergency Help in Virpur, Uttar Pradesh', false);
        $response->assertSee('<meta property="og:url" content="'.route('help.show', $help->id), false);
    }

    public function test_hidden_or_unapproved_help_requests_cannot_be_opened_or_shared(): void
    {
        $pending = HelpRequest::create($this->helpData(['status' => 'Pending']));
        $private = HelpRequest::create($this->helpData(['is_public' => false]));
        $rejected = HelpRequest::create($this->helpData(['status' => 'Rejected']));

        $this->get(route('help.show', $pending->id))->assertNotFound();
        $this->get(route('help.show', $private->id))->assertNotFound();
        $this->get(route('help.show', $rejected->id))->assertNotFound();
        $this->get('/help-each-other/999999')->assertNotFound();
    }

    public function test_request_help_form_route_still_works_alongside_show_route(): void
    {
        $this->get(route('help.create'))->assertOk();
        $this->get(route('help.index'))->assertOk();
    }

    private function helpData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Komal Kushwaha',
            'mobile' => '9000000003',
            'whatsapp' => '9000000003',
            'state' => 'Uttar Pradesh',
            'district' => 'Virpur',
            'city' => 'Virpur',
            'category' => 'Emergency Help',
            'title' => 'Stage 4 cancer treatment support',
            'description' => 'Family needs financial help for ongoing treatment.',
            'urgency' => 'Emergency',
            'is_public' => true,
            'status' => 'Approved',
        ], $overrides);
    }
}
