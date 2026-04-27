<?php

namespace App\Http\Controllers\Club;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Club;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    private const ALLOWED_TABS = ['general', 'hours', 'booking', 'payment', 'notifications'];

    public function index(Request $request): Response|RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $club->load('city:id,name');

        $tab = $request->string('tab')->toString();
        if (! in_array($tab, self::ALLOWED_TABS, true)) {
            $tab = 'general';
        }

        $settings = array_replace_recursive($this->defaultSettings(), $club->settings ?? []);

        return Inertia::render('Club/Settings/Index', [
            'profile' => [
                'id' => $club->id,
                'slug' => $club->slug,
                'name' => [
                    'ar' => $club->getTranslation('name', 'ar'),
                    'en' => $club->getTranslation('name', 'en'),
                ],
                'description' => [
                    'ar' => $club->getTranslation('description', 'ar'),
                    'en' => $club->getTranslation('description', 'en'),
                ],
                'phone_number' => $club->phone_number,
                'email' => $club->email,
                'whatsapp_number' => $club->whatsapp_number,
                'address' => $club->address,
                'city_id' => $club->city_id,
                'city_name' => $club->city?->name,
                'logo_url' => $club->getFirstMediaUrl('logo') ?: null,
                'cover_url' => $club->getFirstMediaUrl('cover') ?: null,
                'settings' => $settings,
                'commission_rate' => $club->commission_rate !== null
                    ? (float) $club->commission_rate
                    : null,
            ],
            'cities' => City::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->map(fn (City $c) => ['id' => $c->id, 'name' => $c->name]),
            'tab' => $tab,
        ]);
    }

    public function updateGeneral(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'name' => ['required', 'array'],
            'name.ar' => ['required', 'string', 'max:255'],
            'name.en' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'array'],
            'description.ar' => ['nullable', 'string', 'max:2000'],
            'description.en' => ['nullable', 'string', 'max:2000'],
            'phone_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'address' => ['required', 'string', 'max:500'],
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'cover' => ['nullable', 'image', 'max:5120'],
        ]);

        $club->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'phone_number' => $validated['phone_number'],
            'email' => $validated['email'] ?? null,
            'whatsapp_number' => $validated['whatsapp_number'] ?? null,
            'address' => $validated['address'],
            'city_id' => $validated['city_id'],
        ]);

        if ($request->hasFile('logo')) {
            $club->clearMediaCollection('logo');
            $club->addMediaFromRequest('logo')->toMediaCollection('logo');
        }
        if ($request->hasFile('cover')) {
            $club->clearMediaCollection('cover');
            $club->addMediaFromRequest('cover')->toMediaCollection('cover');
        }

        activity()
            ->performedOn($club)
            ->causedBy(Auth::user())
            ->log('club_settings_updated_general');

        return back()
            ->with('flash_key', 'settingsUpdated')
            ->with('flash_type', 'success');
    }

    public function updateBusinessHours(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'business_hours' => ['required', 'array', 'size:7'],
            'business_hours.*.day' => ['required', 'string', 'in:saturday,sunday,monday,tuesday,wednesday,thursday,friday'],
            'business_hours.*.is_open' => ['required', 'boolean'],
            'business_hours.*.open_time' => ['nullable', 'required_if:business_hours.*.is_open,true', 'date_format:H:i'],
            'business_hours.*.close_time' => ['nullable', 'required_if:business_hours.*.is_open,true', 'date_format:H:i'],
        ]);

        $this->persistSettings($club, ['business_hours' => array_values($validated['business_hours'])]);

        activity()
            ->performedOn($club)
            ->causedBy(Auth::user())
            ->log('club_settings_updated_hours');

        return back()
            ->with('flash_key', 'settingsUpdated')
            ->with('flash_type', 'success');
    }

    public function updateBookingRules(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'min_booking_hours' => ['required', 'integer', 'min:1', 'max:24'],
            'max_advance_days' => ['required', 'integer', 'min:1', 'max:365'],
            'min_notice_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'allow_same_day' => ['required', 'boolean'],
            'auto_accept' => ['required', 'boolean'],
            'require_deposit' => ['required', 'boolean'],
            'deposit_percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'free_cancellation_hours' => ['required', 'integer', 'min:0', 'max:168'],
            'cancellation_fee_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'no_show_penalty_percentage' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        $this->persistSettings($club, ['booking_rules' => $validated]);

        activity()
            ->performedOn($club)
            ->causedBy(Auth::user())
            ->log('club_settings_updated_booking_rules');

        return back()
            ->with('flash_key', 'settingsUpdated')
            ->with('flash_type', 'success');
    }

    public function updatePaymentMethods(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'accept_cash' => ['required', 'boolean'],
            'accept_syriatel_cash' => ['required', 'boolean'],
            'accept_mtn_cash' => ['required', 'boolean'],
            'accept_bank_transfer' => ['required', 'boolean'],
            'bank_account_details' => ['nullable', 'string', 'max:500'],
        ]);

        $this->persistSettings($club, ['payment_methods' => $validated]);

        activity()
            ->performedOn($club)
            ->causedBy(Auth::user())
            ->log('club_settings_updated_payment');

        return back()
            ->with('flash_key', 'settingsUpdated')
            ->with('flash_type', 'success');
    }

    public function updateNotifications(Request $request): RedirectResponse
    {
        $club = $this->resolveClub();
        if ($club instanceof RedirectResponse) {
            return $club;
        }

        $validated = $request->validate([
            'email_new_booking' => ['required', 'boolean'],
            'email_cancellation' => ['required', 'boolean'],
            'email_new_review' => ['required', 'boolean'],
            'email_payment_received' => ['required', 'boolean'],
            'whatsapp_new_booking' => ['required', 'boolean'],
            'whatsapp_reminder' => ['required', 'boolean'],
        ]);

        $this->persistSettings($club, ['notifications' => $validated]);

        activity()
            ->performedOn($club)
            ->causedBy(Auth::user())
            ->log('club_settings_updated_notifications');

        return back()
            ->with('flash_key', 'settingsUpdated')
            ->with('flash_type', 'success');
    }

    /**
     * Merge a partial-settings patch into the stored JSON blob without discarding other keys.
     */
    private function persistSettings(Club $club, array $patch): void
    {
        $existing = $club->settings ?? [];
        $merged = array_replace_recursive($this->defaultSettings(), $existing, $patch);
        $club->update(['settings' => $merged]);
    }

    /** @return array<string, mixed> */
    private function defaultSettings(): array
    {
        return [
            'business_hours' => $this->defaultBusinessHours(),
            'booking_rules' => [
                'min_booking_hours' => 1,
                'max_advance_days' => 30,
                'min_notice_hours' => 2,
                'allow_same_day' => true,
                'auto_accept' => false,
                'require_deposit' => false,
                'deposit_percentage' => 0,
                'free_cancellation_hours' => 24,
                'cancellation_fee_percentage' => 20,
                'no_show_penalty_percentage' => 50,
            ],
            'payment_methods' => [
                'accept_cash' => true,
                'accept_syriatel_cash' => true,
                'accept_mtn_cash' => true,
                'accept_bank_transfer' => false,
                'bank_account_details' => null,
            ],
            'notifications' => [
                'email_new_booking' => true,
                'email_cancellation' => true,
                'email_new_review' => true,
                'email_payment_received' => true,
                'whatsapp_new_booking' => true,
                'whatsapp_reminder' => true,
            ],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function defaultBusinessHours(): array
    {
        $days = ['saturday', 'sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday'];

        return array_map(fn ($day) => [
            'day' => $day,
            'is_open' => $day !== 'friday',
            'open_time' => '08:00',
            'close_time' => '23:00',
        ], $days);
    }

    private function resolveClub(): Club|RedirectResponse
    {
        $user = Auth::user();

        $club = Club::where('owner_id', $user->id)->first()
            ?? $user->clubs()->first();

        if (! $club) {
            return redirect()
                ->route('club.dashboard')
                ->with('error', 'لا يوجد نادٍ مرتبط بحسابك.');
        }

        return $club;
    }
}
