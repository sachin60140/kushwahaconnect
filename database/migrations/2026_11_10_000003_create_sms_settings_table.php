<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * SMS gateway settings edited by super admins. Kept in their own table so they are
     * never part of the website_settings values shared with every page.
     */
    public function up(): void
    {
        Schema::create('sms_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('enabled')->default(false);
            $table->string('username')->nullable();
            $table->text('password')->nullable(); // encrypted at rest
            $table->string('sender_id', 20)->nullable();
            $table->string('dlt_content_id', 40)->nullable();
            $table->string('dlt_principal_entity_id', 40)->nullable();
            $table->string('base_url')->nullable();
            $table->text('otp_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_settings');
    }
};
