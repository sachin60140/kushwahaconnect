<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\District;
use Illuminate\Support\Facades\Http;

class IndianStatesSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Fetching Indian States and Districts from GitHub...');
        
        $response = Http::get('https://raw.githubusercontent.com/sab99r/Indian-States-And-Districts/master/states-and-districts.json');
        
        if ($response->successful()) {
            $data = $response->json();
            $states = $data['states'];
            
            foreach ($states as $stateData) {
                $stateName = $stateData['state'];
                
                // Create or get the State
                $state = State::firstOrCreate(
                    ['name_en' => $stateName],
                    ['name_hi' => $stateName, 'status' => true]
                );
                
                // Create Districts for the State
                foreach ($stateData['districts'] as $districtName) {
                    District::firstOrCreate(
                        ['state_id' => $state->id, 'name_en' => $districtName],
                        ['name_hi' => $districtName, 'status' => true]
                    );
                }
            }
            
            $this->command->info('Successfully seeded all ' . count($states) . ' States/UTs and their Districts!');
        } else {
            $this->command->error('Failed to fetch data from GitHub.');
        }
    }
}
