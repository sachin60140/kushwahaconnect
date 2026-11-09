<?php

namespace App\Exports;

use App\Models\ContactEnquiry;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ContactExport implements FromCollection, WithHeadings, WithMapping
{
    use MapsAttributeColumns;

    public function collection()
    {
        return ContactEnquiry::orderBy('id')->get();
    }

    protected function columns(): array
    {
        return [
            'ID' => 'id',
            'Name' => 'name',
            'Mobile' => 'mobile',
            'WhatsApp' => 'whatsapp',
            'Email' => 'email',
            'State ID' => 'state_id',
            'District ID' => 'district_id',
            'Purpose' => 'purpose',
            'Message' => 'message',
            'Status' => 'status',
            'Created At' => 'created_at',
            'Updated At' => 'updated_at',
        ];
    }
}
