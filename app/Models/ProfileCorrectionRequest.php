<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProfileCorrectionRequest extends Model
{
    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'resolved' => 'Resolved',
        'rejected' => 'Rejected',
    ];

    protected $fillable = [
        'profile_type',
        'profile_id',
        'profile_name',
        'requester_name',
        'requester_mobile',
        'requester_email',
        'correction_details',
        'source_links',
        'status',
        'admin_note',
    ];
}
