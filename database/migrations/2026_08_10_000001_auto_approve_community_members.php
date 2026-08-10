<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('member_profiles')
            ->where('status', 'pending')
            ->update(['status' => 'approved']);

        DB::table('users')
            ->where('status', 'pending_approval')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('member_profiles')
                    ->whereColumn('member_profiles.user_id', 'users.id')
                    ->where('member_profiles.status', 'approved');
            })
            ->update(['status' => 'approved']);
    }

    public function down(): void
    {
        //
    }
};
