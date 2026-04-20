<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    */

    // Phone
    'phone'                          => 'The :attribute must be a valid Syrian phone number.',
    'phone_taken'                    => 'This phone number is already registered.',

    // Email
    'email_taken'                    => 'This email address is already registered.',

    // Booking
    'venue_not_found'                => 'The selected venue does not exist.',
    'booking_not_found'              => 'The selected booking does not exist.',
    'booking_date_past'              => 'The booking date cannot be in the past.',
    'payment_mode_invalid'           => 'Payment mode must be either full or deposit.',
    'payment_provider_invalid'       => 'The selected payment provider is not supported.',
    'deposit_amount_required'        => 'Deposit amount is required when payment mode is deposit.',
    'deposit_exceeds_price'          => 'Deposit amount cannot exceed the venue price.',
    'cancellation_confirmation_required' => 'You must confirm the cancellation.',
    'duration_multiple_of_30'        => 'Duration must be a multiple of 30 minutes.',

    // Payment
    'payment_not_found'              => 'The selected payment does not exist.',
    'phone_number_required_for_provider' => 'Phone number is required for the selected payment provider.',

    // Date
    'date_in_past'                   => 'The date cannot be in the past.',
    'to_date_before_from'            => 'The end date must be after or equal to the start date.',

    // Venue
    'closing_before_opening'         => 'Closing time must be after opening time.',
    'city_not_found'                 => 'The selected city does not exist.',

    // Club
    'club_not_found'                 => 'The selected club does not exist.',

    // Auth
    'role_invalid'                   => 'The selected role is invalid.',

    /*
    |--------------------------------------------------------------------------
    | Attribute Names
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'name'             => 'name',
        'name_ar'          => 'Arabic name',
        'phone'            => 'phone number',
        'email'            => 'email address',
        'password'         => 'password',
        'otp'              => 'OTP code',
        'totp_code'        => 'authenticator code',
        'id_token'         => 'Google ID token',
        'venue_id'         => 'venue',
        'booking_id'       => 'booking',
        'payment_id'       => 'payment',
        'club_id'          => 'club',
        'city_id'          => 'city',
        'category_id'      => 'category',
        'booking_date'     => 'booking date',
        'start_time'       => 'start time',
        'duration_minutes' => 'duration',
        'payment_mode'     => 'payment mode',
        'payment_provider' => 'payment provider',
        'deposit_amount'   => 'deposit amount',
        'confirmed'        => 'confirmation',
        'rating'           => 'rating',
        'query'            => 'search query',
        'date'             => 'date',
        'preferred_date'   => 'preferred date',
        'from_date'        => 'start date',
        'to_date'          => 'end date',
        'is_active'        => 'active status',
        'address'          => 'address',
        'opening_hours'    => 'opening hours',
        'price_per_hour'   => 'price per hour',
        'reason'           => 'reason',
        'role'             => 'role',
        'avatar'           => 'profile photo',
        'sport_ids'        => 'sports',
    ],
];
