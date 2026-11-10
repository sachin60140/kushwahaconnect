<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_otps', function (Blueprint $table) {
            $table->id();
            $table->string('mobile', 20);
            $table->string('purpose', 20);
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('last_sent_at');
            $table->timestamps();

            $table->unique(['mobile', 'purpose']);
        });

        // OTPs now live (hashed) in mobile_otps; drop the old plaintext columns.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['otp_code', 'otp_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code')->nullable()->after('password');
            $table->timestamp('otp_expires_at')->nullable()->after('otp_code');
        });

        Schema::dropIfExists('mobile_otps');
    }
};
