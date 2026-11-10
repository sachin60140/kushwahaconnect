<?php

use App\Services\Sms\SmartPingSms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Store every mobile as a bare 10-digit number ("+91 98765 43210" → "9876543210")
 * so OTP login finds the account and the unique check sees duplicates.
 *
 * Rows that cannot be normalised, or whose normalised number another row already
 * uses, are left exactly as they are and logged by id (never by number) for an
 * admin to sort out. Nothing is merged or deleted; the unique index stays intact.
 * Verification state is untouched: it is the same number, only reformatted.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->normalize('users');
        $this->normalize('member_profiles');
    }

    public function down(): void
    {
        // Formatting only; the original spacing/prefixes are not restorable.
    }

    private function normalize(string $table): void
    {
        $rows = DB::table($table)->whereNotNull('mobile')->orderBy('id')->get(['id', 'mobile']);
        $taken = $rows->pluck('mobile')->map(fn ($m) => (string) $m)->flip()->all();
        $skipped = [];

        foreach ($rows as $row) {
            $current = (string) $row->mobile;
            $normal = SmartPingSms::normalizeMobile($current);

            if ($normal === null || $normal === $current) {
                if ($normal === null && $current !== '') {
                    $skipped[] = ['id' => $row->id, 'reason' => 'not a valid 10-digit mobile'];
                }

                continue;
            }

            // users.mobile is unique; member_profiles is kept consistent the same way.
            if (isset($taken[$normal])) {
                $skipped[] = ['id' => $row->id, 'reason' => 'normalised number already used by another row'];

                continue;
            }

            DB::table($table)->where('id', $row->id)->update(['mobile' => $normal]);
            unset($taken[$current]);
            $taken[$normal] = true;
        }

        if ($skipped !== []) {
            Log::warning("Mobile normalisation left some {$table} rows unchanged; please review them.", ['rows' => $skipped]);
        }
    }
};
