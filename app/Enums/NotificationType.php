<?php

namespace App\Enums;

enum NotificationType: string
{
    case BookingConfirmed = 'booking_confirmed';
    case BookingReminder = 'booking_reminder';
    case BookingCancelled = 'booking_cancelled';
    case BookingCompleted = 'booking_completed';

    case PaymentReceived = 'payment_received';
    case PaymentFailed = 'payment_failed';
    case RefundProcessed = 'refund_processed';

    case PromoAvailable = 'promo_available';
    case PromoExpiring = 'promo_expiring';

    case ReviewRequest = 'review_request';
    case ReviewHelpful = 'review_helpful';

    case SystemAnnouncement = 'system_announcement';
    case VenueAdded = 'venue_added';

    public function label(): string
    {
        $key = 'notifications.types.'.$this->value;
        $translated = __($key);

        return $translated === $key ? $this->value : $translated;
    }

    public function isPromotional(): bool
    {
        return in_array($this, [
            self::PromoAvailable,
            self::PromoExpiring,
            self::VenueAdded,
        ], true);
    }
}
