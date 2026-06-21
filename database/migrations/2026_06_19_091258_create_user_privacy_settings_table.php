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
        Schema::create('user_privacy_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // General Community Profile Visibility
            $table->boolean('public_profile_visible')->default(true); // Is the profile visible in the community directory?
            $table->string('mobile_visibility')->default('hidden'); // hidden, connections_only, public
            $table->string('email_visibility')->default('hidden'); // hidden, connections_only, public
            $table->string('photo_visibility')->default('public'); // hidden, connections_only, public
            $table->string('address_visibility')->default('city_only'); // city_only, full, hidden
            
            // Matrimonial Specific Privacy
            $table->boolean('matrimonial_show_first_name_only')->default(false);
            $table->string('matrimonial_photo_visibility')->default('public'); // public, accepted_interests_only, hidden
            $table->boolean('matrimonial_hide_income')->default(false);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_privacy_settings');
    }
};
