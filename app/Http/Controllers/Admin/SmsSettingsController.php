<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsSetting;
use App\Services\Otp\OtpService;
use App\Services\Sms\SmartPingSms;
use App\Services\Sms\SmsConfig;
use App\Services\Sms\SmsHealth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Super admin → SMS Settings. The password is write-only: it is stored encrypted and
 * never sent back to the browser.
 */
class SmsSettingsController extends Controller
{
    public function show(Request $request): Response
    {
        $saved = SmsSetting::current();
        $defaults = (array) config('services.smartping');
        $effective = SmsConfig::resolve();

        return Inertia::render('Admin/SmsSettings', [
            'settings' => [
                'enabled' => $saved ? $saved->enabled : $effective['enabled'],
                'username' => $saved?->username ?? '',
                'sender_id' => $saved?->sender_id ?? '',
                'dlt_content_id' => $saved?->dlt_content_id ?? '',
                'dlt_principal_entity_id' => $saved?->dlt_principal_entity_id ?? '',
                'base_url' => $saved?->base_url ?? '',
                'otp_message' => $saved?->otp_message ?? '',
            ],
            // Non-secret fallbacks from .env / config, shown as placeholders.
            'defaults' => [
                'sender_id' => $defaults['sender_id'] ?? '',
                'dlt_content_id' => $defaults['dlt_content_id'] ?? '',
                'dlt_principal_entity_id' => $defaults['dlt_principal_entity_id'] ?? '',
                'base_url' => $defaults['base_url'] ?? '',
                'otp_message' => $defaults['otp_message'] ?? '',
            ],
            'hasSavedPassword' => filled($saved?->getRawOriginal('password')),
            'hasEnvUsername' => filled($defaults['username'] ?? null),
            'hasEnvPassword' => filled($defaults['password'] ?? null),
            'configured' => SmsConfig::configured($effective),
            'testResult' => $request->session()->get('sms_test_result'),
            'smsHealth' => SmsHealth::usage(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled' => ['required', 'boolean'],
            'username' => ['nullable', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:255'],
            'sender_id' => ['nullable', 'string', 'max:20'],
            'dlt_content_id' => ['nullable', 'regex:/^\d{10,30}$/'],
            'dlt_principal_entity_id' => ['nullable', 'regex:/^\d{10,30}$/'],
            'base_url' => ['nullable', 'url:https', 'max:255', function ($attribute, $value, $fail) {
                if (filled($value) && ! SmartPingSms::allowedBaseUrl($value)) {
                    $fail('sms_settings_error_url');
                }
            }],
            'otp_message' => ['nullable', 'string', 'max:500', 'regex:/\{otp\}/'],
        ], [
            'dlt_content_id.regex' => 'sms_settings_error_dlt_id',
            'dlt_principal_entity_id.regex' => 'sms_settings_error_dlt_id',
            'base_url.url' => 'sms_settings_error_url',
            'otp_message.regex' => 'sms_settings_error_template',
        ]);

        $settings = SmsSetting::current() ?? new SmsSetting;

        // Blank password keeps the saved one.
        if (blank($validated['password'] ?? null)) {
            unset($validated['password']);
        }

        foreach (['username', 'sender_id', 'dlt_content_id', 'dlt_principal_entity_id', 'base_url', 'otp_message'] as $field) {
            $validated[$field] = filled($validated[$field] ?? null) ? trim($validated[$field]) : null;
        }

        $settings->fill($validated)->save();

        Log::info('SMS settings updated.', ['by_user_id' => $request->user()->id, 'enabled' => $settings->enabled]);

        return redirect()->route('admin.sms-settings.show')->with('success', 'sms_settings_saved');
    }

    public function test(Request $request): RedirectResponse
    {
        $request->validate([
            'mobile' => ['required', 'string', 'regex:/^(\+?91|0)?[6-9]\d{9}$/'],
        ], [
            'mobile.regex' => 'sms_settings_error_mobile',
        ]);

        $config = SmsConfig::resolve();
        $code = str_pad((string) random_int(0, 10 ** OtpService::LENGTH - 1), OtpService::LENGTH, '0', STR_PAD_LEFT);
        $message = str_replace('{otp}', $code, (string) $config['otp_message']);

        // Uses the saved credentials even while sending is switched off, so they can be checked first.
        $result = (new SmartPingSms($config))->deliver($request->input('mobile'), $message);

        Log::info('SMS test sent from admin.', [
            'by_user_id' => $request->user()->id,
            'ok' => $result['ok'],
            'state' => $result['state'],
            'status_code' => $result['status_code'],
        ]);

        return redirect()->route('admin.sms-settings.show')->with('sms_test_result', $result);
    }

    /**
     * "Retry SMS now": end a degraded episode early so OTP is enforced again
     * and the next send goes to SmartPing normally.
     */
    public function retry(Request $request): RedirectResponse
    {
        SmsHealth::clear('manual retry by user #'.$request->user()->id);

        return redirect()->route('admin.sms-settings.show')->with('success', 'sms_health_retry_done');
    }
}
