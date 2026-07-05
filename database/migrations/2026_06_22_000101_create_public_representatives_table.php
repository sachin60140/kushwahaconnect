<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('public_representatives', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('photo_path')->nullable();
            $table->enum('representative_status', ['current', 'former'])->default('current');
            $table->string('category');
            $table->string('current_designation')->nullable();
            $table->text('former_designations')->nullable();
            $table->string('political_party')->nullable();
            $table->string('constituency')->nullable();
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->string('house_or_body')->nullable();
            $table->date('term_start_date')->nullable();
            $table->date('term_end_date')->nullable();
            $table->text('biography_hi')->nullable();
            $table->text('biography_en')->nullable();
            $table->string('official_website')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('x_url')->nullable();
            $table->string('government_source_url')->nullable();
            $table->string('election_source_url')->nullable();
            $table->date('last_verified_at')->nullable();
            $table->text('verification_note')->nullable();
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('is_published')->default(false);
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['is_published', 'representative_status', 'is_featured', 'display_order'], 'pr_pub_status_feat_order_idx');
            $table->index(['category', 'state', 'district'], 'pr_cat_state_dist_idx');
            $table->index(['political_party', 'constituency'], 'pr_party_const_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('public_representatives');
    }
};
