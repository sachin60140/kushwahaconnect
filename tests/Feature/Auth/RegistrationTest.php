<?php

namespace Tests\Feature\Auth;

use App\Models\District;
use App\Models\State;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_redirects_to_join_flow(): void
    {
        $response = $this->get('/register');

        $response->assertRedirect(route('join', absolute: false));
    }

    public function test_join_screen_can_be_rendered(): void
    {
        $response = $this->get('/join');

        $response->assertStatus(200);
    }

    public function test_new_users_can_join_the_community(): void
    {
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

        $response = $this->post('/join', [
            'full_name' => 'Test User',
            'father_husband_name' => 'Test Parent',
            'gender' => 'Male',
            'mobile' => '9876543210',
            'whatsapp' => '9876543210',
            'email' => 'test@example.com',
            'state_id' => $state->id,
            'district_id' => $district->id,
            'city_village' => 'Patna',
            'pin_code' => '800001',
            'current_address' => 'Patna, Bihar',
            'consent' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('dashboard', absolute: false));

        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'mobile' => '9876543210',
            'status' => 'pending_approval',
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'full_name' => 'Test User',
            'mobile' => '9876543210',
            'status' => 'pending',
        ]);
    }
}
