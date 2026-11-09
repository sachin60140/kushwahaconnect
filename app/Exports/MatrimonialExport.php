<?php

namespace App\Exports;

use App\Models\MatrimonialProfile;
use Illuminate\Support\Enumerable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MatrimonialExport implements FromCollection, WithHeadings, WithMapping
{
    use MapsAttributeColumns;

    public function collection(): Enumerable
    {
        return MatrimonialProfile::orderBy('id')->get();
    }

    protected function columns(): array
    {
        return [
            'ID' => 'id',
            'User ID' => 'user_id',
            'Profile For' => 'profile_for',
            'Full Name' => 'full_name',
            'Gender' => 'gender',
            'DOB' => 'dob',
            'Height' => 'height',
            'Marital Status' => 'marital_status',
            'Manglik' => 'manglik',
            'Gotra' => 'gotra',
            'Mother Tongue' => 'mother_tongue',
            'Current City' => 'current_city',
            'Native Place' => 'native_place',
            'Education' => 'education',
            'College' => 'college',
            'Occupation' => 'occupation',
            'Company' => 'company',
            'Annual Income' => 'annual_income',
            'Work Location' => 'work_location',
            'Father Name' => 'father_name',
            'Father Occupation' => 'father_occupation',
            'Mother Name' => 'mother_name',
            'Mother Occupation' => 'mother_occupation',
            'Brothers' => 'brothers',
            'Sisters' => 'sisters',
            'Family Type' => 'family_type',
            'Family Location' => 'family_location',
            'Partner Age Pref' => 'pref_age_range',
            'Partner Height Pref' => 'pref_height',
            'Partner Education Pref' => 'pref_education',
            'Partner Location Pref' => 'pref_location',
            'Partner Profession Pref' => 'pref_profession',
            'Other Expectations' => 'other_expectations',
            'Mobile' => 'mobile',
            'WhatsApp' => 'whatsapp',
            'Email' => 'email',
            'Is Public' => 'is_public',
            'Hide Photo' => 'hide_photo',
            'Consent' => 'consent',
            'Status' => 'status',
            'Verified' => 'is_verified',
            'Admin Note' => 'admin_note',
            'Created At' => 'created_at',
            'Updated At' => 'updated_at',
        ];
    }
}
