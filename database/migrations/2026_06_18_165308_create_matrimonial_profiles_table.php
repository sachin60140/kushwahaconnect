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
        Schema::create('matrimonial_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->string('profile_for');
            $table->string('full_name');
            $table->string('gender');
            $table->date('dob');
            $table->string('height')->nullable();
            $table->string('marital_status');
            $table->string('manglik')->nullable();
            $table->string('gotra')->nullable();
            $table->string('mother_tongue')->nullable();
            $table->string('current_city')->nullable();
            $table->string('native_place')->nullable();
            $table->string('education')->nullable();
            $table->string('college')->nullable();
            $table->string('occupation')->nullable();
            $table->string('company')->nullable();
            $table->string('annual_income')->nullable();
            $table->string('work_location')->nullable();
            $table->string('father_name')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('mother_name')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->integer('brothers')->default(0);
            $table->integer('sisters')->default(0);
            $table->string('family_type')->nullable();
            $table->string('family_location')->nullable();
            $table->string('pref_age_range')->nullable();
            $table->string('pref_height')->nullable();
            $table->string('pref_education')->nullable();
            $table->string('pref_location')->nullable();
            $table->string('pref_profession')->nullable();
            $table->text('other_expectations')->nullable();
            $table->string('photo')->nullable();
            $table->string('id_proof')->nullable();
            $table->string('mobile');
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_public')->default(false);
            $table->boolean('hide_photo')->default(false);
            $table->boolean('consent')->default(true);
            $table->string('status')->default('pending');
            $table->boolean('is_verified')->default(false);
            $table->text('admin_note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matrimonial_profiles');
    }
};
