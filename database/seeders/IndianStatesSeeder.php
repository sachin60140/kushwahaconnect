<?php

namespace Database\Seeders;

use App\Models\District;
use App\Models\State;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class IndianStatesSeeder extends Seeder
{
    private const LEGACY_STATE_ALIASES = [
        'Andaman and Nicobar Islands' => ['Andaman and Nicobar', 'Andaman & Nicobar Islands'],
        'Chandigarh' => ['Chandigarh (UT)'],
        'Dadra and Nagar Haveli and Daman and Diu' => [
            'Dadra and Nagar Haveli (UT)',
            'Dadra Nagar Haveli, Daman and Diu',
            'Daman and Diu (UT)',
        ],
        'Delhi' => ['Delhi (NCT)', 'National Capital Territory of Delhi'],
        'Lakshadweep' => ['Lakshadweep (UT)'],
        'Puducherry' => ['Pondicherry', 'Puducherry (UT)'],
    ];

    public function run(): void
    {
        $path = database_path('data/india_states_districts.json');

        if (! File::exists($path)) {
            throw new \RuntimeException("India state/district seed data not found at {$path}.");
        }

        $payload = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        $states = $payload['states'] ?? [];
        $districtCount = 0;

        foreach ($states as $stateData) {
            $stateName = trim($stateData['name_en'] ?? '');

            if ($stateName === '') {
                continue;
            }

            $state = $this->stateFor($stateName);
            $state->fill([
                'name_en' => $stateName,
                'name_hi' => trim($stateData['name_hi'] ?? '') ?: $stateName,
                'code' => $stateData['code'] ?? $state->code,
                'status' => true,
            ])->save();

            $activeDistricts = [];

            foreach ($stateData['districts'] ?? [] as $districtData) {
                $districtName = trim($districtData['name_en'] ?? '');

                if ($districtName === '') {
                    continue;
                }

                $activeDistricts[] = $districtName;

                District::updateOrCreate(
                    ['state_id' => $state->id, 'name_en' => $districtName],
                    [
                        'name_hi' => trim($districtData['name_hi'] ?? '') ?: $districtName,
                        'status' => true,
                    ],
                );

                $districtCount++;
            }

            District::where('state_id', $state->id)
                ->whereNotIn('name_en', $activeDistricts)
                ->update(['status' => false]);
        }

        foreach (self::LEGACY_STATE_ALIASES as $canonical => $aliases) {
            State::whereIn('name_en', $aliases)
                ->where('name_en', '!=', $canonical)
                ->update(['status' => false]);
        }

        $this->command?->info("Seeded {$districtCount} districts across ".count($states).' states/UTs.');
    }

    private function stateFor(string $stateName): State
    {
        $state = State::where('name_en', $stateName)->first();

        if ($state) {
            return $state;
        }

        $aliases = self::LEGACY_STATE_ALIASES[$stateName] ?? [];

        if ($aliases !== []) {
            $state = State::whereIn('name_en', $aliases)->first();

            if ($state) {
                return $state;
            }
        }

        return new State;
    }
}
