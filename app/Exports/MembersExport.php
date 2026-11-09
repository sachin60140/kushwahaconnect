<?php

namespace App\Exports;

use App\Models\MemberProfile;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class MembersExport implements FromCollection, WithHeadings, WithMapping
{
    use MapsAttributeColumns;

    public function collection()
    {
        return MemberProfile::with(['state:id,name_en', 'district:id,name_en'])->orderBy('id')->get();
    }

    protected function columns(): array
    {
        return [
            'ID' => 'id',
            'User ID' => 'user_id',
            'Full Name' => 'full_name',
            'Father/Husband Name' => 'father_husband_name',
            'Gender' => 'gender',
            'DOB' => 'dob',
            'Mobile' => 'mobile',
            'WhatsApp' => 'whatsapp',
            'Email' => 'email',
            'State' => 'state.name_en',
            'District' => 'district.name_en',
            'City/Village' => 'city_village',
            'Pin Code' => 'pin_code',
            'Current Address' => 'current_address',
            'Native Place' => 'native_place',
            'Education' => 'education',
            'Occupation' => 'occupation',
            'Employment Type' => 'employment_type',
            'Company Name' => 'company_name',
            'Skills' => 'skills',
            'Gotra' => 'gotra',
            'Social Work Interest' => 'social_work_interest',
            'Volunteer Interest' => 'volunteer_interest',
            'Join District Team' => 'join_district_team',
            'Facebook' => 'facebook',
            'Instagram' => 'instagram',
            'LinkedIn' => 'linkedin',
            'YouTube' => 'youtube',
            'Twitter' => 'twitter',
            'Is Public' => 'is_public',
            'Show Mobile' => 'show_mobile',
            'Allow WhatsApp' => 'allow_whatsapp',
            'Consent' => 'consent',
            'Status' => 'status',
            'Admin Note' => 'admin_note',
            'Created At' => 'created_at',
            'Updated At' => 'updated_at',
        ];
    }
}
