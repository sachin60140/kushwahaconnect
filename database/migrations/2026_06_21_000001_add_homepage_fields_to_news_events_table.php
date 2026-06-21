<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_events', function (Blueprint $table) {
            if (!Schema::hasColumn('news_events', 'event_location')) {
                $table->string('event_location')->nullable()->after('event_date');
            }

            if (!Schema::hasColumn('news_events', 'registration_url')) {
                $table->string('registration_url')->nullable()->after('event_location');
            }
        });
    }

    public function down(): void
    {
        Schema::table('news_events', function (Blueprint $table) {
            if (Schema::hasColumn('news_events', 'registration_url')) {
                $table->dropColumn('registration_url');
            }

            if (Schema::hasColumn('news_events', 'event_location')) {
                $table->dropColumn('event_location');
            }
        });
    }
};
