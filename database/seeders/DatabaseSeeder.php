<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\State;
use App\Models\District;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Admin
        User::firstOrCreate(
            ['email' => 'admin@kushwahaconnect.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        // Create States & Districts
        $states = [
            'Bihar' => ['Patna', 'Gaya', 'Bhagalpur', 'Muzaffarpur', 'Nalanda'],
            'Uttar Pradesh' => ['Lucknow', 'Kanpur', 'Varanasi', 'Agra', 'Prayagraj'],
            'Madhya Pradesh' => ['Bhopal', 'Indore', 'Jabalpur', 'Gwalior', 'Ujjain']
        ];

        foreach ($states as $stateName => $districts) {
            $state = State::firstOrCreate([
                'name_en' => $stateName,
                'name_hi' => $stateName, // Dummy translation for now
                'code' => strtoupper(substr($stateName, 0, 3)),
                'status' => true
            ]);

            foreach ($districts as $districtName) {
                District::firstOrCreate([
                    'state_id' => $state->id,
                    'name_en' => $districtName,
                    'name_hi' => $districtName,
                    'status' => true
                ]);
            }
        }
    }
}
