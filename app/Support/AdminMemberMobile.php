<?php

namespace App\Support;

use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Otp\VerifiedMobiles;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * One set of rules for a super admin changing a member's mobile, shared by the
 * quick "Edit mobile" action and the full member edit form.
 */
class AdminMemberMobile
{
    /** Store bare 10-digit numbers; unparseable input is left as typed for the format rule. */
    public static function normalizeRequest(Request $request, string $field = 'mobile'): void
    {
        if ($request->has($field)) {
            $request->merge([$field => VerifiedMobiles::normalize((string) $request->input($field)) ?? $request->input($field)]);
        }
    }

    /** @return array<int, mixed> */
    public static function rules(MemberProfile $member): array
    {
        return [
            'required', 'string', 'regex:/^[6-9]\d{9}$/',
            Rule::unique(User::class, 'mobile')->ignore($member->user_id),
        ];
    }

    /** @return array<string, string> */
    public static function messages(string $field = 'mobile'): array
    {
        return [
            $field.'.regex' => 'otp_error_mobile_format',
            $field.'.unique' => 'admin_mobile_taken',
        ];
    }

    /**
     * Apply a validated number to the profile and its account. A changed account
     * number loses its verification (User::booted()) until the member verifies again
     * by OTP. Call inside a transaction. Returns the changed field names.
     *
     * @return list<string>
     */
    public static function apply(MemberProfile $member, string $mobile): array
    {
        $changed = [];

        if ($member->mobile !== $mobile) {
            $member->update(['mobile' => $mobile]);
            $changed[] = 'mobile';
        }

        if ($member->user && $member->user->mobile !== $mobile) {
            // A super admin's change is trusted for OTP login; verification is cleared
            // (User::booted()) and set again by the member's next OTP login or verify.
            $member->user->mobile = $mobile;
            $member->user->save();
            $changed[] = 'user.mobile';
        }

        return $changed;
    }
}
