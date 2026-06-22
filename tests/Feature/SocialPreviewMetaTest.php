<?php

namespace Tests\Feature;

use App\Models\Gallery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialPreviewMetaTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_pages_render_server_side_social_preview_tags(): void
    {
        $response = $this->get(route('welcome'));

        $response->assertOk();
        $response->assertSee('property="og:title"', false);
        $response->assertSee('property="og:description"', false);
        $response->assertSee('property="og:image"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('KushwahaConnect - Digital Community Platform for Kushwaha Samaj', false);
        $response->assertSee('images/kushwahaconnect-social-preview.png', false);
    }

    public function test_gallery_show_uses_gallery_content_for_social_preview(): void
    {
        $gallery = Gallery::create([
            'title' => 'Patna Community Event',
            'description' => 'Photos from the Patna Kushwaha community gathering.',
            'thumbnail' => 'gallery/patna-event.jpg',
            'is_published' => true,
        ]);

        $response = $this->get(route('gallery.show', $gallery));

        $response->assertOk();
        $response->assertSee('Patna Community Event - KushwahaConnect', false);
        $response->assertSee('Photos from the Patna Kushwaha community gathering.', false);
        $response->assertSee('storage/gallery/patna-event.jpg', false);
        $response->assertSee('property="og:type" content="article"', false);
    }

    public function test_private_pages_are_marked_noindex(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('name="robots" content="noindex, nofollow"', false);
    }
}
