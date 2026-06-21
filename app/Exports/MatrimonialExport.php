<?php

namespace App\Exports;

use App\Models\MatrimonialProfile;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MatrimonialExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return MatrimonialProfile::all();
    }

    public function headings(): array
    {
        return [
            'ID', 'User ID', 'Profile For', 'Full Name', 'Gender', 'DOB', 'Birth Time', 'Birth Place',
            'Height', 'Marital Status', 'Manglik', 'Gotra', 'Mother Tongue',
            'Current City', 'Native Place',
            'Education', 'College', 'Occupation', 'Company', 'Annual Income', 'Work Location',
            'Father Name', 'Father Occupation', 'Mother Name', 'Mother Occupation', 'Siblings Info',
            'Partner Age Pref', 'Partner Height Pref', 'Partner Education Pref', 'Partner Occupation Pref', 'Other Prefs',
            'Mobile', 'Email', 'Consent', 'Status', 'Created At', 'Updated At'
        ];
    }
}
