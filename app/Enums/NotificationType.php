<?php

namespace App\Enums;

enum NotificationType: string
{
    case BookingConfirmed = 'booking_confirmed';
    case BookingReminder = 'booking_reminder';
    case BookingCancelled = 'booking_cancelled';
    case BookingCompleted = 'booking_completed';
    case BookingRescheduled = 'booking_rescheduled';

    case PaymentReceived = 'payment_received';
    case PaymentFailed = 'payment_failed';
    case PaymentRetry = 'payment_retry';
    case RefundProcessed = 'refund_processed';
    case RefundCompleted = 'refund_completed';
    case SettlementPaid = 'settlement_paid';

    case PromoAvailable = 'promo_available';
    case PromoExpiring = 'promo_expiring';

    case ReviewRequest = 'review_request';
    case ReviewHelpful = 'review_helpful';

    case SystemAnnouncement = 'system_announcement';
    case VenueAdded = 'venue_added';

    case ClubApproved = 'club_approved';
    case ClubRejected = 'club_rejected';
    case WaitlistAvailable = 'waitlist_available';

    case GoalScored = 'goal_scored';
    case MatchResult = 'match_result';
    case MatchReminder = 'match_reminder';
    case DailyMatchSummary = 'daily_match_summary';

    case EventReminder = 'event_reminder';
    case AchievementUnlocked = 'achievement_unlocked';
    case EmergencyReported = 'emergency_reported';

    case GroupBookingInvite = 'group_booking_invite';
    case TeamInvitation = 'team_invitation';

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
