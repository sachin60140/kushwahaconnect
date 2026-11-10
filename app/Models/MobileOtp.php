<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileOtp extends Model
{
    protected $fillable = ['mobile', 'purpose', 'code_hash', 'attempts', 'expires_at', 'last_sent_at'];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }
}
