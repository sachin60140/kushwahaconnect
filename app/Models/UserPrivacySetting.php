<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserPrivacySetting extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'public_profile_visible' => 'boolean',
        'matrimonial_show_first_name_only' => 'boolean',
        'matrimonial_hide_income' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
