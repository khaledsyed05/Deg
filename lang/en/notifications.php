<?php

return [
    'types' => [
        'booking_confirmed' => 'Booking confirmed',
        'booking_reminder' => 'Booking reminder',
        'booking_cancelled' => 'Booking cancelled',
        'booking_completed' => 'Booking completed',
        'booking_rescheduled' => 'Booking rescheduled',
        'payment_received' => 'Payment received',
        'payment_failed' => 'Payment failed',
        'payment_retry' => 'Payment retried',
        'refund_processed' => 'Refund processed',
        'refund_completed' => 'Refund completed',
        'settlement_paid' => 'Settlement paid',
        'promo_available' => 'Offer available',
        'promo_expiring' => 'Offer expiring soon',
        'review_request' => 'Leave a review',
        'review_helpful' => 'Helpful review',
        'system_announcement' => 'System announcement',
        'venue_added' => 'New venue',
        'club_approved' => 'Club approved',
        'club_rejected' => 'Club rejected',
        'waitlist_available' => 'Slot available',
        'goal_scored' => 'Goal scored',
        'match_result' => 'Match result',
        'match_reminder' => 'Match reminder',
        'daily_match_summary' => "Today's matches",
        'event_reminder' => 'Event reminder',
        'achievement_unlocked' => 'Achievement unlocked',
        'emergency_reported' => 'Emergency reported',
        'group_booking_invite' => 'Group booking invite',
        'team_invitation' => 'Team invitation',
    ],

    'booking_confirmed' => [
        'title' => 'Booking confirmed',
        'body' => 'Your booking at :venue on :date is confirmed',
    ],
    'booking_reminder' => [
        'title' => 'Booking reminder',
        'body' => 'You have an upcoming booking at :venue at :time',
    ],
    'booking_cancelled' => [
        'title' => 'Booking cancelled',
        'body' => 'Your booking at :venue has been cancelled',
    ],
    'booking_completed' => [
        'title' => 'Booking completed',
        'body' => 'Thanks for using Daq Ehjezly',
    ],
    'booking_rescheduled' => [
        'title' => 'Booking rescheduled',
        'body' => 'Your booking at :venue has been rescheduled',
    ],
    'payment_received' => [
        'title' => 'Payment received',
        'body' => 'Payment of :amount SYP received successfully',
    ],
    'payment_failed' => [
        'title' => 'Payment failed',
        'body' => 'Your payment could not be processed. Please try again',
    ],
    'payment_retry' => [
        'title' => 'Payment retried',
        'body' => 'Your payment of :amount SYP has been retried',
    ],
    'refund_processed' => [
        'title' => 'Refund processed',
        'body' => ':amount SYP has been refunded to your wallet',
    ],
    'refund_completed' => [
        'title' => 'Refund completed',
        'body' => ':amount SYP has been refunded successfully',
    ],
    'settlement_paid' => [
        'title' => 'Settlement paid',
        'body' => 'Your settlement of :amount SYP has been transferred',
    ],
    'review_request' => [
        'title' => 'Rate your experience',
        'body' => 'Share your experience at :venue',
    ],
    'review_helpful' => [
        'title' => 'Your review was helpful',
        'body' => 'Another user found your review helpful',
    ],
    'promo_available' => [
        'title' => 'New offer',
        'body' => 'Get :discount% off your next booking',
    ],
    'promo_expiring' => [
        'title' => 'Offer expiring soon',
        'body' => 'Use your promo code before it expires',
    ],
    'system_announcement' => [
        'title' => 'Announcement',
        'body' => '',
    ],
    'venue_added' => [
        'title' => 'New venue',
        'body' => 'A new venue has been added near you',
    ],
    'club_approved' => [
        'title' => 'Your club was approved ✅',
        'body' => ':club has been approved and is now bookable.',
    ],
    'club_rejected' => [
        'title' => 'Club application rejected',
        'body' => ':club application was rejected. Reason: :reason',
    ],
    'waitlist_available' => [
        'title' => 'Your requested slot is available!',
        'body' => 'The slot on :date at :time is now available to book.',
    ],
    'goal_scored' => [
        'title' => '⚽ Goal! :team',
        'body' => ':home :score :away (:minute)',
    ],
    'match_result' => [
        'title' => '🏁 Match result',
        'body' => ':home :score :away',
    ],
    'match_reminder' => [
        'title' => '🏟️ Match reminder',
        'body' => ':home vs :away',
    ],
    'daily_match_summary' => [
        'title' => "📅 Today's matches",
        'body' => 'You have :count match(es) for your favourite teams today',
    ],
    'event_reminder' => [
        'title' => 'Event reminder',
        'body' => ':event starts :window',
    ],
    'achievement_unlocked' => [
        'title' => 'Achievement unlocked! 🏆',
        'body' => 'You earned: :achievement',
    ],
    'emergency_reported' => [
        'title' => '🚨 Emergency reported',
        'body' => 'A :severity emergency has been reported',
    ],
    'group_booking_invite' => [
        'title' => 'Group booking invite',
        'body' => ':inviter invited you to a booking at :venue',
    ],
    'team_invitation' => [
        'title' => 'Team invitation',
        'body' => ':inviter invited you to join the :team team',
    ],

    'default' => [
        'title' => 'New notification',
    ],
];
