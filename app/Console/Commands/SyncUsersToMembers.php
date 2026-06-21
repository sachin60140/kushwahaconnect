<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\MemberProfile;

class SyncUsersToMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-users-to-members';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Merges any existing registered users into the MemberProfiles table if they do not have a profile.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to sync existing users to MemberProfiles...');

        // Get all users who do not have a member profile
        $usersWithoutProfile = User::whereDoesntHave('memberProfile')->get();

        $count = 0;
        foreach ($usersWithoutProfile as $user) {
            // Because they registered before we added Mobile and Gender fields,
            // we will provide default values that they can update later.
            MemberProfile::create([
                'user_id' => $user->id,
                'full_name' => $user->name,
                'email' => $user->email,
                'mobile' => 'Not Provided',
                'gender' => 'Other', // Or default if string
                'is_public' => true,
                'status' => 'approved',
            ]);
            $count++;
        }

        $this->info("Successfully merged {$count} existing users into the Members Directory!");
    }
}
