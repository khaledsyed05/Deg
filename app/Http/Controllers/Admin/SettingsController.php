<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /** Canonical schema: group → [ [name, type, default, options?] ]. */
    private function schema(): array
    {
        return [
            'general' => [
                ['name' => 'platform_name_ar', 'type' => 'string', 'default' => 'دق احجزلي'],
                ['name' => 'platform_name_en', 'type' => 'string', 'default' => 'Daq Ehjezly'],
                ['name' => 'support_email', 'type' => 'string', 'default' => ''],
                ['name' => 'support_phone', 'type' => 'string', 'default' => ''],
                ['name' => 'currency', 'type' => 'string', 'default' => 'SYP', 'options' => ['SYP', 'USD', 'EUR']],
                ['name' => 'default_language', 'type' => 'string', 'default' => 'ar', 'options' => ['ar', 'en']],
            ],
            'booking' => [
                ['name' => 'min_booking_advance_hours', 'type' => 'int', 'default' => 2],
                ['name' => 'max_booking_advance_days', 'type' => 'int', 'default' => 90],
                ['name' => 'default_booking_duration_hours', 'type' => 'int', 'default' => 2],
                ['name' => 'allow_same_day_bookings', 'type' => 'bool', 'default' => true],
                ['name' => 'require_deposit_payment', 'type' => 'bool', 'default' => true],
                ['name' => 'deposit_percentage', 'type' => 'int', 'default' => 50],
                ['name' => 'cancellation_deadline_hours', 'type' => 'int', 'default' => 24],
                ['name' => 'auto_cancel_unpaid_hours', 'type' => 'int', 'default' => 48],
            ],
            'payment' => [
                ['name' => 'syriatel_cash_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'mtn_cash_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'fatora_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'sama_pay_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'wallet_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'payment_timeout_minutes', 'type' => 'int', 'default' => 15],
                ['name' => 'auto_refund_on_cancellation', 'type' => 'bool', 'default' => false],
            ],
            'notifications' => [
                ['name' => 'whatsapp_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'email_enabled', 'type' => 'bool', 'default' => false],
                ['name' => 'sms_enabled', 'type' => 'bool', 'default' => false],
                ['name' => 'notify_booking_confirmation', 'type' => 'bool', 'default' => true],
                ['name' => 'notify_payment_success', 'type' => 'bool', 'default' => true],
                ['name' => 'notify_cancellation', 'type' => 'bool', 'default' => true],
                ['name' => 'notify_settlement', 'type' => 'bool', 'default' => true],
            ],
            'features' => [
                ['name' => 'player_registration_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'club_registration_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'venue_search_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'favorites_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'reviews_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'ratings_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'recurring_bookings_enabled', 'type' => 'bool', 'default' => false],
                ['name' => 'group_bookings_enabled', 'type' => 'bool', 'default' => false],
            ],
            'maintenance' => [
                ['name' => 'maintenance_mode', 'type' => 'bool', 'default' => false],
                ['name' => 'maintenance_message', 'type' => 'string', 'default' => ''],
                ['name' => 'registration_closed', 'type' => 'bool', 'default' => false],
                ['name' => 'read_only_mode', 'type' => 'bool', 'default' => false],
                ['name' => 'debug_mode', 'type' => 'bool', 'default' => false],
            ],
            'analytics' => [
                ['name' => 'player_events_tracking_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'anonymous_tracking_enabled', 'type' => 'bool', 'default' => true],
                ['name' => 'session_recording_enabled', 'type' => 'bool', 'default' => false],
                ['name' => 'google_analytics_id', 'type' => 'string', 'default' => ''],
                ['name' => 'facebook_pixel_id', 'type' => 'string', 'default' => ''],
            ],
            'security' => [
                ['name' => 'force_https', 'type' => 'bool', 'default' => true],
                ['name' => 'password_min_length', 'type' => 'int', 'default' => 8],
                ['name' => 'password_require_special_char', 'type' => 'bool', 'default' => true],
                ['name' => 'max_login_attempts', 'type' => 'int', 'default' => 5],
                ['name' => 'lockout_duration_minutes', 'type' => 'int', 'default' => 15],
                ['name' => 'session_timeout_minutes', 'type' => 'int', 'default' => 120],
            ],
        ];
    }

    public function index(): Response
    {
        $this->ensureSeeded();

        $existing = Setting::all()->keyBy('name');
        $groups = [];

        foreach ($this->schema() as $group => $entries) {
            $groups[$group] = [];
            foreach ($entries as $entry) {
                $row = $existing->get($entry['name']);
                $groups[$group][] = [
                    'name' => $entry['name'],
                    'type' => $entry['type'],
                    'value' => $row ? $row->value : $entry['default'],
                    'options' => $entry['options'] ?? null,
                    'locked' => (bool) ($row?->locked ?? false),
                ];
            }
        }

        return Inertia::render('Admin/Settings/System', [
            'groups' => $groups,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*.name' => ['required', 'string'],
            'settings.*.value' => ['present'],
            'settings.*.type' => ['required', 'in:string,int,bool,float,array'],
        ]);

        $schemaFlat = [];
        foreach ($this->schema() as $group => $entries) {
            foreach ($entries as $e) {
                $schemaFlat[$e['name']] = ['group' => $group, 'type' => $e['type']];
            }
        }

        $changed = 0;
        foreach ($data['settings'] as $entry) {
            $declared = $schemaFlat[$entry['name']] ?? null;
            if (! $declared) {
                // Unknown setting — skip silently (prevents arbitrary writes).
                continue;
            }
            if ($declared['type'] !== $entry['type']) {
                // Type mismatch — skip.
                continue;
            }

            $castValue = Setting::cast($entry['value'], $entry['type']);
            $existing = Setting::where('name', $entry['name'])->first();
            $oldValue = $existing?->value;

            if ($existing?->locked) {
                continue;
            }

            Setting::put($entry['name'], $castValue, $entry['type'], $declared['group']);

            if ($oldValue !== $castValue) {
                activity('setting')
                    ->causedBy($request->user())
                    ->withProperties([
                        'name' => $entry['name'],
                        'old_value' => $oldValue,
                        'new_value' => $castValue,
                    ])
                    ->log('setting_updated');
                $changed++;
            }
        }

        return back()
            ->with('flash_key', $changed > 0 ? 'settingsUpdated' : 'settingsNoChanges')
            ->with('flash_type', 'success');
    }

    public function toggleMaintenanceMode(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            'message' => ['nullable', 'string', 'max:500'],
        ]);

        Setting::put('maintenance_mode', $data['enabled'], 'bool', 'maintenance');
        Setting::put('maintenance_message', $data['message'] ?? '', 'string', 'maintenance');

        activity('setting')
            ->causedBy($request->user())
            ->withProperties($data)
            ->log('maintenance_mode_toggled');

        return back()
            ->with('flash_key', 'maintenanceModeUpdated')
            ->with('flash_type', 'success');
    }

    /** Seed defaults for any missing keys. Idempotent. */
    private function ensureSeeded(): void
    {
        $existing = Setting::pluck('name')->all();
        foreach ($this->schema() as $group => $entries) {
            foreach ($entries as $entry) {
                if (! in_array($entry['name'], $existing, true)) {
                    Setting::put($entry['name'], $entry['default'], $entry['type'], $group);
                }
            }
        }
    }
}
