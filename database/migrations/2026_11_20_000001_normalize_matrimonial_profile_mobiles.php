<?php

use App\Services\Sms\SmartPingSms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Store matrimonial mobiles as bare 10-digit numbers ("+91 99822 11163" → "9982211163"),
 * as the public form and the member editor now do, so duplicate checks and the admin
 * "Duplicates only" filter see one format.
 *
 * Rows that cannot be normalised are left exactly as they are and logged by id (never by
 * number). There is no unique index on this column, so several profiles may end up with
 * the same number: nothing is merged or deleted; admins clean up duplicates themselves.
 */
return new class extends Migration
{
    public function up(): void
    {
        $skipped = [];

        DB::table('matrimonial_profiles')->whereNotNull('mobile')->orderBy('id')->select(['id', 'mobile'])
            ->chunkById(500, function ($rows) use (&$skipped) {
                foreach ($rows as $row) {
                    $current = (string) $row->mobile;
                    $normal = SmartPingSms::normalizeMobile($current);

                    if ($normal === null) {
                        if ($current !== '') {
                            $skipped[] = ['id' => $row->id, 'reason' => 'not a valid 10-digit mobile'];
                        }

                        continue;
                    }

                    if ($normal !== $current) {
                        DB::table('matrimonial_profiles')->where('id', $row->id)->update(['mobile' => $normal]);
                    }
                }
            });

        if ($skipped !== []) {
            Log::warning('Mobile normalisation left some matrimonial_profiles rows unchanged; please review them.', ['rows' => $skipped]);
        }
    }

    public function down(): void
    {
        // Formatting only; the original spacing/prefixes are not restorable.
    }
};
