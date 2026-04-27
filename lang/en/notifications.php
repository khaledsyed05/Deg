<?php

return [
    'types' => [
        'booking_confirmed' => 'Booking confirmed',
        'booking_reminder' => 'Booking reminder',
        'booking_cancelled' => 'Booking cancelled',
        'booking_completed' => 'Booking completed',
        'payment_received' => 'Payment received',
        'payment_failed' => 'Payment failed',
        'refund_processed' => 'Refund processed',
        'promo_available' => 'Offer available',
        'promo_expiring' => 'Offer expiring soon',
        'review_request' => 'Leave a review',
        'review_helpful' => 'Helpful review',
        'system_announcement' => 'System announcement',
        'venue_added' => 'New venue',
    ],

    'booking_confirmed' => [
        'title' => 'Booking confirmed',
        'body' => 'Your booking at :venue on :date is confirmed',
    ],
    'booking_reminder' => [
        'title' => 'Booking reminder',
        'body' => 'You have a booking tomorrow at :venue at :time',
    ],
    'booking_cancelled' => [
        'title' => 'Booking cancelled',
        'body' => 'Your booking at :venue has been cancelled',
    ],
    'booking_completed' => [
        'title' => 'Booking completed',
        'body' => 'Thanks for using Daq Ehjezly',
    ],
    'payment_received' => [
        'title' => 'Payment received',
        'body' => 'Payment of :amount SYP received successfully',
    ],
    'payment_failed' => [
        'title' => 'Payment failed',
        'body' => 'Your payment could not be processed. Please try again',
    ],
    'refund_processed' => [
        'title' => 'Refund processed',
        'body' => ':amount SYP has been refunded to your wallet',
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

    'default' => [
        'title' => 'New notification',
    ],
];
