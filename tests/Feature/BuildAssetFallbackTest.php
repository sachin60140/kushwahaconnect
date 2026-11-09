<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * When public_html/build on the host is stale, missing /build files are
 * rewritten to Laravel, which must serve them from the project's public/build.
 */
class BuildAssetFallbackTest extends TestCase
{
    public function test_missing_web_root_assets_are_served_from_project_build(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $appJs = $manifest['resources/js/app.js']['file'];

        $response = $this->get('/build/'.$appJs);

        $response->assertOk();
        $this->assertStringStartsWith('application/javascript', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('immutable', $response->headers->get('Cache-Control'));
        $this->assertEmpty($response->headers->getCookies(), 'Static assets must not start a session or set cookies.');
    }

    public function test_css_assets_get_css_content_type(): void
    {
        $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
        $css = $manifest['resources/js/app.js']['css'][0];

        $response = $this->get('/build/'.$css);

        $response->assertOk();
        $this->assertStringStartsWith('text/css', $response->headers->get('Content-Type'));
    }

    public function test_unknown_or_escaping_paths_return_404(): void
    {
        $this->get('/build/assets/does-not-exist.js')->assertNotFound();
        $this->get('/build/..%2F..%2F.env')->assertNotFound();
        $this->get('/build/assets/..%2F..%2F..%2Fcomposer.json')->assertNotFound();
    }
}
