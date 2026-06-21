<?php

namespace App\Exports;

use App\Models\ContactEnquiry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ContactExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return ContactEnquiry::all();
    }

    public function headings(): array
    {
        return [
            'ID', 'Name', 'Mobile', 'WhatsApp', 'Email', 'State ID', 'District ID', 'Purpose', 'Message', 'Status', 'Created At', 'Updated At'
        ];
    }
}
