<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Event dates were saved as the India-time wall clock admins typed, in a column read
 * as UTC (so "upcoming" ran 5h30 late). Convert them to the real UTC moment; the app
 * now converts admin input from India time on save and shows dates in India time.
 */
return new class extends Migration
{
    private const ENTERED_ZONE = 'Asia/Kolkata';

    public function up(): void
    {
        $this->convert(self::ENTERED_ZONE, 'UTC');
    }

    public function down(): void
    {
        $this->convert('UTC', self::ENTERED_ZONE);
    }

    private function convert(string $from, string $to): void
    {
        DB::table('news_events')
            ->whereNotNull('event_date')
            ->select(['id', 'event_date'])
            ->lazyById()
            ->each(function ($row) use ($from, $to) {
                DB::table('news_events')->where('id', $row->id)->update([
                    'event_date' => Carbon::parse($row->event_date, $from)->setTimezone($to)->format('Y-m-d H:i:s'),
                ]);
            });
    }
};
