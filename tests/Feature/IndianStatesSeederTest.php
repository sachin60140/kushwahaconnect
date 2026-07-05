<?php

namespace Tests\Feature;

use App\Models\District;
use App\Models\State;
use Database\Seeders\IndianStatesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndianStatesSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_india_states_and_districts_from_local_data(): void
    {
        $this->seed(IndianStatesSeeder::class);

        $this->assertSame(36, State::where('status', true)->count());
        $this->assertSame(791, District::where('status', true)->count());

        $this->assertDistrictExists('Bihar', 'Patna');
        $this->assertDistrictExists('Delhi', 'Old Delhi');
        $this->assertDistrictExists('Goa', 'Kushavati');
        $this->assertDistrictExists('Ladakh', 'Leh');
        $this->assertDistrictExists('Nagaland', 'Meluri');
        $this->assertDistrictExists('Uttar Pradesh', 'Varanasi');
    }

    public function test_it_reuses_legacy_state_rows_and_deactivates_stale_districts(): void
    {
        $legacyState = State::create([
            'name_en' => 'Delhi (NCT)',
            'name_hi' => 'Delhi (NCT)',
            'code' => 'DEL',
            'status' => true,
        ]);

        District::create([
            'state_id' => $legacyState->id,
            'name_en' => 'Shahdara',
            'name_hi' => 'Shahdara',
            'status' => true,
        ]);

        $this->seed(IndianStatesSeeder::class);

        $legacyState->refresh();

        $this->assertSame('Delhi', $legacyState->name_en);
        $this->assertEquals(true, $legacyState->status);
        $this->assertTrue($legacyState->districts()->where('name_en', 'Old Delhi')->where('status', true)->exists());
        $this->assertTrue($legacyState->districts()->where('name_en', 'Shahdara')->where('status', false)->exists());
    }

    private function assertDistrictExists(string $stateName, string $districtName): void
    {
        $state = State::where('name_en', $stateName)->where('status', true)->firstOrFail();

        $this->assertTrue(
            District::where('state_id', $state->id)
                ->where('name_en', $districtName)
                ->where('status', true)
                ->exists(),
            "{$districtName} was not seeded for {$stateName}.",
        );
    }
}
