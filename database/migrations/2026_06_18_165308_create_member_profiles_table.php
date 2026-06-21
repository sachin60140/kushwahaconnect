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
        Schema::create('member_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('full_name');
            $table->string('father_husband_name')->nullable();
            $table->string('gender');
            $table->date('dob')->nullable();
            $table->string('mobile');
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('profile_photo')->nullable();
            $table->foreignId('state_id')->nullable()->constrained('states');
            $table->foreignId('district_id')->nullable()->constrained('districts');
            $table->string('city_village')->nullable();
            $table->string('pin_code')->nullable();
            $table->text('current_address')->nullable();
            $table->text('native_place')->nullable();
            $table->string('education')->nullable();
            $table->string('occupation')->nullable();
            $table->string('employment_type')->nullable();
            $table->string('company_name')->nullable();
            $table->string('skills')->nullable();
            $table->string('gotra')->nullable();
            $table->string('social_work_interest')->nullable();
            $table->boolean('volunteer_interest')->default(false);
            $table->boolean('join_district_team')->default(false);
            $table->string('facebook')->nullable();
            $table->string('instagram')->nullable();
            $table->string('linkedin')->nullable();
            $table->string('youtube')->nullable();
            $table->string('twitter')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('show_mobile')->default(false);
            $table->boolean('allow_whatsapp')->default(true);
            $table->boolean('consent')->default(true);
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('member_profiles');
    }
};
