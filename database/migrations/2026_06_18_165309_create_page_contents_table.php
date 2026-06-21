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
        Schema::create('page_contents', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_hi');
            $table->longText('content_en')->nullable();
            $table->longText('content_hi')->nullable();
            $table->string('seo_title_en')->nullable();
            $table->string('seo_title_hi')->nullable();
            $table->text('seo_desc_en')->nullable();
            $table->text('seo_desc_hi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_contents');
    }
};
