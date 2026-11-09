<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matrimonial_profiles', function (Blueprint $table) {
            // Private file on the local disk (matrimonial_biodata/...), served via an access-checked route.
            $table->string('biodata')->nullable()->after('id_proof');
        });
    }

    public function down(): void
    {
        Schema::table('matrimonial_profiles', function (Blueprint $table) {
            $table->dropColumn('biodata');
        });
    }
};
