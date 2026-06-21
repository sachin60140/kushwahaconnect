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
        Schema::create('help_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('name');
            $table->string('mobile');
            $table->string('whatsapp')->nullable();
            $table->string('state');
            $table->string('district');
            $table->string('city');
            $table->string('category');
            $table->string('title');
            $table->text('description');
            $table->enum('urgency', ['Normal', 'Urgent', 'Emergency'])->default('Normal');
            $table->enum('status', ['Pending', 'Approved', 'Assigned', 'In Progress', 'Resolved', 'Rejected'])->default('Pending');
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('help_requests');
    }
};
