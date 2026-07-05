<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('personality_nominations', function (Blueprint $table) {
            $table->id();
            $table->string('person_name');
            $table->string('category');
            $table->string('profession_or_designation')->nullable();
            $table->string('state')->nullable();
            $table->string('district')->nullable();
            $table->text('achievement')->nullable();
            $table->text('biography')->nullable();
            $table->string('photograph_path')->nullable();
            $table->string('supporting_document_path')->nullable();
            $table->text('source_links')->nullable();
            $table->text('social_links')->nullable();
            $table->string('nominator_name');
            $table->string('nominator_mobile');
            $table->string('relationship')->nullable();
            $table->boolean('consent')->default(false);
            $table->string('status')->default('submitted');
            $table->text('admin_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('personality_nominations');
    }
};
