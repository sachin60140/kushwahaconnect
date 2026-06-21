<?php

namespace Tests\Feature;

use App\Models\DataDeletionRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_privacy_terms_and_data_deletion_pages_render(): void
    {
        $this->get(route('privacy'))->assertOk();
        $this->get(route('terms'))->assertOk();
        $this->get(route('data-deletion'))->assertOk();
    }

    public function test_visitors_can_submit_data_deletion_request(): void
    {
        $response = $this->post(route('data-deletion.store'), [
            'name' => 'Test Member',
            'mobile' => '9000000000',
            'email' => 'member@example.com',
            'reason' => 'Please delete my account and community profile.',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('data_deletion_requests', [
            'name' => 'Test Member',
            'mobile' => '9000000000',
            'email' => 'member@example.com',
            'status' => 'pending',
        ]);

        $this->assertSame(1, DataDeletionRequest::count());
    }
}
