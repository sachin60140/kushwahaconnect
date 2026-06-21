<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('mobile')->unique()->nullable()->after('email');
            $table->timestamp('mobile_verified_at')->nullable()->after('mobile');
            $table->string('otp_code')->nullable()->after('password');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
            $table->string('status')->default('profile_incomplete')->after('role'); // profile_incomplete, pending_approval, approved, rejected, suspended, blocked
            $table->json('roles')->nullable()->after('status'); // To support multiple roles JSON array
            $table->string('language_preference')->default('en')->after('roles');
            
            // Note: email is already unique and nullable might be needed if they only register via mobile,
            // but for now we'll keep email unique and nullable manually if needed. Let's make email nullable.
            $table->string('email')->nullable()->change();
            $table->string('password')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'mobile',
                'mobile_verified_at',
                'otp_code',
                'otp_expires_at',
                'status',
                'roles',
                'language_preference'
            ]);
            $table->string('email')->nullable(false)->change();
            $table->string('password')->nullable(false)->change();
        });
    }
};
