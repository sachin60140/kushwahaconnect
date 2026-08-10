<?php

namespace Tests\Feature\Auth;

use App\Models\District;
use App\Models\State;
use App\Models\User;
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
            'status' => 'approved',
        ]);

        $this->assertDatabaseHas('member_profiles', [
            'full_name' => 'Test User',
            'mobile' => '9876543210',
            'status' => 'approved',
        ]);
    }

    public function test_new_users_can_join_without_an_email_address(): void
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
            'full_name' => 'No Email User',
            'father_husband_name' => 'Test Parent',
            'gender' => 'Male',
            'mobile' => '9876543211',
            'whatsapp' => '9876543211',
            'email' => '',
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
            'name' => 'No Email User',
            'mobile' => '9876543211',
            'email' => null,
            'status' => 'approved',
        ]);
    }

    public function test_join_validation_rejects_duplicate_mobile_and_email(): void
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

        User::factory()->create([
            'mobile' => '9876543212',
            'email' => 'existing@example.com',
        ]);

        $response = $this->post('/join', [
            'full_name' => 'Duplicate User',
            'father_husband_name' => 'Test Parent',
            'gender' => 'Male',
            'mobile' => '9876543212',
            'whatsapp' => '9876543212',
            'email' => 'existing@example.com',
            'state_id' => $state->id,
            'district_id' => $district->id,
            'city_village' => 'Patna',
            'pin_code' => '800001',
            'current_address' => 'Patna, Bihar',
            'consent' => '1',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors(['mobile', 'email']);
        $this->assertDatabaseMissing('member_profiles', [
            'full_name' => 'Duplicate User',
        ]);
    }
}
