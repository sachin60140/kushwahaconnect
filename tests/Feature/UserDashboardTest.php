<?php

namespace Tests\Feature;

use App\Models\BusinessListing;
use App\Models\District;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_current_users_submitted_business_statuses(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
            'status' => 'approved',
        ]);
        $otherUser = User::factory()->create([
            'role' => 'user',
            'status' => 'approved',
        ]);
        $state = State::create([
            'name_en' => 'Bihar',
            'name_hi' => 'Bihar',
            'code' => 'BR',
            'status' => true,
        ]);
        $district = District::create([
            'state_id' => $state->id,
            'name_en' => 'Patna',
            'name_hi' => 'Patna',
            'status' => true,
        ]);

        BusinessListing::create($this->businessData($state, $district, $user, [
            'business_name' => 'Pending Shop',
            'status' => 'pending',
        ]));
        BusinessListing::create($this->businessData($state, $district, $user, [
            'business_name' => 'Approved Services',
            'status' => 'approved',
        ]));
        BusinessListing::create($this->businessData($state, $district, $user, [
            'business_name' => 'Rejected Store',
            'status' => 'rejected',
        ]));
        BusinessListing::create($this->businessData($state, $district, $otherUser, [
            'business_name' => 'Other User Business',
            'status' => 'approved',
        ]));

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();

        $businesses = collect($response->viewData('page')['props']['businessListings']);

        $this->assertCount(3, $businesses);
        $this->assertEqualsCanonicalizing(
            ['Pending Shop', 'Approved Services', 'Rejected Store'],
            $businesses->pluck('business_name')->all()
        );
        $this->assertEqualsCanonicalizing(
            ['pending', 'approved', 'rejected'],
            $businesses->pluck('status')->all()
        );
        $this->assertFalse($businesses->pluck('business_name')->contains('Other User Business'));
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
}
