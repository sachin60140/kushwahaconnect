<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('kushwaha_pride_profiles')) {
            Schema::create('kushwaha_pride_profiles', function (Blueprint $table) {
                $table->id();
                $table->string('name_hi');
                $table->string('name_en');
                $table->string('photo_path')->nullable();
                $table->string('category');
                $table->string('designation_hi')->nullable();
                $table->string('designation_en')->nullable();
                $table->string('state')->nullable();
                $table->string('district')->nullable();
                $table->string('city')->nullable();
                $table->text('biography_hi')->nullable();
                $table->text('biography_en')->nullable();
                $table->text('achievements_hi')->nullable();
                $table->text('achievements_en')->nullable();
                $table->string('organisation')->nullable();
                $table->string('official_website')->nullable();
                $table->string('facebook_url')->nullable();
                $table->string('instagram_url')->nullable();
                $table->string('linkedin_url')->nullable();
                $table->string('youtube_url')->nullable();
                $table->string('x_url')->nullable();
                $table->text('verification_sources')->nullable();
                $table->date('last_verified_at')->nullable();
                $table->boolean('is_verified')->default(false);
                $table->boolean('is_featured')->default(false);
                $table->unsignedInteger('display_order')->default(0);
                $table->boolean('is_published')->default(false);
                $table->timestamp('archived_at')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('kushwaha_pride_profiles', function (Blueprint $table) {
            if (! $this->hasIndex('kushwaha_pride_profiles', 'kpp_pub_feat_order_idx')) {
                $table->index(['is_published', 'is_featured', 'display_order'], 'kpp_pub_feat_order_idx');
            }

            if (! $this->hasIndex('kushwaha_pride_profiles', 'kpp_cat_state_dist_idx')) {
                $table->index(['category', 'state', 'district'], 'kpp_cat_state_dist_idx');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kushwaha_pride_profiles');
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))
            ->contains(fn (array $index) => ($index['name'] ?? null) === $name);
    }
};
