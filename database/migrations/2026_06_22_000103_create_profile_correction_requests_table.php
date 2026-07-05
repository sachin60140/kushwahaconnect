<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('profile_correction_requests', function (Blueprint $table) {
            $table->id();
            $table->string('profile_type');
            $table->unsignedBigInteger('profile_id')->nullable();
            $table->string('profile_name')->nullable();
            $table->string('requester_name');
            $table->string('requester_mobile')->nullable();
            $table->string('requester_email')->nullable();
            $table->text('correction_details');
            $table->text('source_links')->nullable();
            $table->string('status')->default('submitted');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['profile_type', 'profile_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_correction_requests');
    }
};
