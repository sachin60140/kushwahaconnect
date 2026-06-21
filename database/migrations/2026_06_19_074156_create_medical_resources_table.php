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
        Schema::create('medical_resources', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', ['blood_request', 'doctor', 'hospital', 'other']);
            $table->text('description');
            $table->string('contact_info');
            $table->string('blood_group')->nullable();
            $table->string('urgency')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_resources');
    }
};
