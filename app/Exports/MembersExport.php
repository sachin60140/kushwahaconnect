<?php

namespace App\Exports;

use App\Models\MemberProfile;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MembersExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return MemberProfile::all();
    }

    public function headings(): array
    {
        return [
            'ID', 'User ID', 'Full Name', 'Father/Husband Name', 'Gender', 'DOB', 'Mobile', 'WhatsApp', 'Email',
            'State ID', 'District ID', 'City/Village', 'Pin Code', 'Current Address', 'Native Place',
            'Education', 'Occupation', 'Employment Type', 'Company Name', 'Skills', 'Gotra',
            'Social Work Interest', 'Volunteer Interest', 'Join District Team',
            'Facebook', 'Instagram', 'LinkedIn', 'YouTube', 'Twitter',
            'Is Public', 'Show Mobile', 'Allow WhatsApp', 'Consent', 'Status', 'Created At', 'Updated At'
        ];
    }
}
