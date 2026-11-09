<?php

namespace Tests\Feature;

use App\Models\HelpRequest;
use App\Support\HelpPreviewImage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_shared_link_previews_with_a_generated_help_card_image(): void
    {
        Storage::fake('local');
        $help = HelpRequest::create($this->helpData());
        $imageUrl = route('help.preview', ['id' => $help->id, 'v' => HelpPreviewImage::fingerprint($help)]);

        $this->get(route('help.show', $help->id))
            ->assertSee('<meta property="og:image" content="'.e($imageUrl).'"', false)
            ->assertSee('<meta name="twitter:image" content="'.e($imageUrl).'"', false);

        $response = $this->get($imageUrl);
        $response->assertOk();
        $this->assertSame('image/png', $response->headers->get('Content-Type'));

        $size = getimagesize($response->getFile()->getPathname());
        $this->assertSame([1200, 630], [$size[0], $size[1]]);

        // Cached per content: one file, reused on the next request.
        $this->assertCount(1, Storage::disk('local')->files('og'));
        $this->get($imageUrl)->assertOk();
        $this->assertCount(1, Storage::disk('local')->files('og'));
    }

    public function test_preview_card_renders_for_hindi_text_and_replaces_outdated_cards(): void
    {
        Storage::fake('local');
        $help = HelpRequest::create($this->helpData([
            'title' => 'मेरे बेटे की पढ़ाई के लिए सहायता चाहिए',
            'city' => 'पटना',
        ]));

        $this->get(route('help.preview', $help->id))->assertOk();

        $help->update(['title' => 'Updated appeal title']);
        $this->get(route('help.preview', $help->id))->assertOk();

        $files = Storage::disk('local')->files('og');
        $this->assertCount(1, $files);
        $this->assertStringContainsString(HelpPreviewImage::fingerprint($help->fresh()), $files[0]);
    }

    public function test_preview_image_is_not_available_for_hidden_requests(): void
    {
        $pending = HelpRequest::create($this->helpData(['status' => 'Pending']));

        $this->get(route('help.preview', $pending->id))->assertNotFound();
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
