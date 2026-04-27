<?php

namespace App\Services\Emergency;

use App\Models\Booking;
use App\Models\Emergency\EmergencyContact;
use App\Models\Emergency\EmergencyLocationShare;
use App\Models\Emergency\EmergencyReport;
use App\Models\User;
use App\Notifications\Emergency\EmergencyReportedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EmergencyService
{
    /**
     * @param  array<string, mixed>|null  $location
     * @param  array<int, mixed>  $attachments
     */
    public function reportEmergency(
        User $user,
        string $type,
        string $description,
        ?array $location = null,
        ?int $bookingId = null,
        string $severity = 'medium',
        ?string $contactPhone = null,
        array $attachments = []
    ): EmergencyReport {
        return DB::transaction(function () use (
            $user,
            $type,
            $description,
            $location,
            $bookingId,
            $severity,
            $contactPhone,
            $attachments
        ) {
            $venueId = null;
            if ($bookingId) {
                $booking = Booking::find($bookingId);
                if ($booking && $booking->user_id === $user->id) {
                    $venueId = $booking->venue_id;
                }
            }

            $report = EmergencyReport::create([
                'user_id' => $user->id,
                'booking_id' => $bookingId,
                'venue_id' => $venueId,
                'type' => $type,
                'severity' => $severity,
                'description' => $description,
                'latitude' => $location['latitude'] ?? null,
                'longitude' => $location['longitude'] ?? null,
                'location_address' => $location['address'] ?? null,
                'contact_phone' => $contactPhone ?? $user->phone_number,
                'attachments' => $attachments,
                'status' => 'reported',
            ]);

            Log::channel('emergencies')->warning('Emergency report received', [
                'report_id' => $report->id,
                'user_id' => $user->id,
                'type' => $type,
                'severity' => $severity,
                'venue_id' => $venueId,
                'booking_id' => $bookingId,
            ]);

            $this->notifyAdminsOfEmergency($report);

            if ($venueId) {
                Log::channel('emergencies')->info('Venue notified of emergency', [
                    'report_id' => $report->id,
                    'venue_id' => $venueId,
                ]);
            }

            return $report;
        });
    }

    /**
     * @param  array<int, string>  $recipientPhones
     */
    public function startLocationShare(
        User $user,
        ?int $bookingId = null,
        array $recipientPhones = [],
        ?string $messageToRecipients = null,
        int $durationMinutes = 60
    ): EmergencyLocationShare {
        $share = EmergencyLocationShare::create([
            'user_id' => $user->id,
            'booking_id' => $bookingId,
            'recipient_phones' => $recipientPhones,
            'message_to_recipients' => $messageToRecipients,
            'starts_at' => now(),
            'expires_at' => now()->addMinutes($durationMinutes),
            'is_active' => true,
        ]);

        Log::channel('emergencies')->info('Location share started', [
            'share_id' => $share->id,
            'user_id' => $user->id,
            'duration_minutes' => $durationMinutes,
            'recipients_count' => count($recipientPhones),
        ]);

        return $share;
    }

    public function updateLocationShare(string $token, float $latitude, float $longitude): bool
    {
        $share = EmergencyLocationShare::where('share_token', $token)
            ->where('is_active', true)
            ->where('expires_at', '>', now())
            ->first();

        if (! $share) {
            return false;
        }

        $share->update([
            'last_latitude' => $latitude,
            'last_longitude' => $longitude,
            'last_updated_at' => now(),
        ]);

        return true;
    }

    public function stopLocationShare(int $shareId, User $user): void
    {
        $share = EmergencyLocationShare::where('id', $shareId)
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->firstOrFail();

        $share->update([
            'is_active' => false,
            'stopped_at' => now(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getEmergencyContacts(?int $cityId = null): array
    {
        $query = EmergencyContact::active();

        if ($cityId) {
            $query->where(function ($q) use ($cityId) {
                $q->whereJsonContains('coverage_areas', $cityId)
                    ->orWhereNull('coverage_areas')
                    ->orWhereJsonLength('coverage_areas', 0);
            });
        }

        return $query->get()
            ->groupBy('type')
            ->map(fn ($contacts, $type) => [
                'type' => $type,
                'type_ar' => $this->getTypeLabel($type),
                'contacts' => $contacts->map(fn (EmergencyContact $c) => [
                    'id' => $c->id,
                    'name_ar' => $c->name_ar,
                    'phone' => $c->phone,
                    'alternative_phone' => $c->alternative_phone,
                    'icon_name' => $c->icon_name,
                ])->values(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getSafetyGuide(): array
    {
        return Cache::remember('safety_guide', 86400, fn () => [
            'sections' => [
                [
                    'title_ar' => 'قبل اللعب',
                    'icon' => 'shield-check',
                    'tips' => [
                        'تأكد من ارتداء المعدات الواقية المناسبة',
                        'قم بالإحماء جيداً قبل البدء',
                        'تأكد من سلامة الملعب وخلوه من العوائق',
                        'اشرب كمية كافية من الماء',
                    ],
                ],
                [
                    'title_ar' => 'أثناء اللعب',
                    'icon' => 'activity',
                    'tips' => [
                        'احترم قواعد اللعبة',
                        'تجنب التصرفات العنيفة',
                        'إذا شعرت بأي ألم، توقف فوراً',
                        'حافظ على تواصل مع زملائك',
                    ],
                ],
                [
                    'title_ar' => 'حالات الطوارئ',
                    'icon' => 'alert-circle',
                    'tips' => [
                        'في حالة الإصابة، اضغط زر الطوارئ في التطبيق',
                        'احتفظ بأرقام الطوارئ في متناول يدك',
                        'أبلغ النادي عن أي حادث',
                        'اتصل بالإسعاف (110) في الحالات الخطيرة',
                    ],
                ],
            ],
            'emergency_numbers' => [
                ['name_ar' => 'الإسعاف', 'phone' => '110'],
                ['name_ar' => 'الإطفاء', 'phone' => '113'],
                ['name_ar' => 'الشرطة', 'phone' => '112'],
                ['name_ar' => 'دعم دق احجزلي', 'phone' => '+963991234567'],
            ],
        ]);
    }

    private function notifyAdminsOfEmergency(EmergencyReport $report): void
    {
        try {
            $admins = User::role('admin')->get();
            foreach ($admins as $admin) {
                $admin->notify(new EmergencyReportedNotification($report));
            }
        } catch (\Throwable $e) {
            Log::channel('emergencies')->warning('Admin notify failed', [
                'report_id' => $report->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function getTypeLabel(string $type): string
    {
        return match ($type) {
            'police' => 'الشرطة',
            'ambulance' => 'الإسعاف',
            'fire' => 'الإطفاء',
            'civil_defense' => 'الدفاع المدني',
            'support' => 'الدعم الفني',
            'platform_emergency' => 'طوارئ المنصة',
            default => $type,
        };
    }
}
