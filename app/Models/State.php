<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['name_en', 'name_hi', 'code', 'status'];

    public function districts()
    {
        return $this->hasMany(District::class);
    }
}
