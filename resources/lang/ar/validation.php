<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines — Arabic
    |--------------------------------------------------------------------------
    */

    // Phone
    'phone'                          => 'يجب أن يكون :attribute رقم هاتف سوري صحيح.',
    'phone_taken'                    => 'رقم الهاتف هذا مسجل مسبقاً.',

    // Email
    'email_taken'                    => 'البريد الإلكتروني هذا مسجل مسبقاً.',

    // Booking
    'venue_not_found'                => 'الملعب المحدد غير موجود.',
    'booking_not_found'              => 'الحجز المحدد غير موجود.',
    'booking_date_past'              => 'لا يمكن أن يكون تاريخ الحجز في الماضي.',
    'payment_mode_invalid'           => 'طريقة الدفع يجب أن تكون إما كاملة أو عربون.',
    'payment_provider_invalid'       => 'مزود الدفع المحدد غير مدعوم.',
    'deposit_amount_required'        => 'مبلغ العربون مطلوب عند اختيار الدفع بالعربون.',
    'deposit_exceeds_price'          => 'لا يمكن أن يتجاوز مبلغ العربون سعر الملعب.',
    'cancellation_confirmation_required' => 'يجب تأكيد الإلغاء.',
    'duration_multiple_of_30'        => 'يجب أن تكون المدة من مضاعفات 30 دقيقة.',

    // Payment
    'payment_not_found'              => 'عملية الدفع المحددة غير موجودة.',
    'phone_number_required_for_provider' => 'رقم الهاتف مطلوب لمزود الدفع المحدد.',

    // Date
    'date_in_past'                   => 'لا يمكن أن يكون التاريخ في الماضي.',
    'to_date_before_from'            => 'يجب أن يكون تاريخ الانتهاء بعد أو يساوي تاريخ البداية.',

    // Venue
    'closing_before_opening'         => 'وقت الإغلاق يجب أن يكون بعد وقت الافتتاح.',
    'city_not_found'                 => 'المدينة المحددة غير موجودة.',

    // Club
    'club_not_found'                 => 'النادي المحدد غير موجود.',

    // Auth
    'role_invalid'                   => 'الدور المحدد غير صالح.',

    /*
    |--------------------------------------------------------------------------
    | Attribute Names — Arabic
    |--------------------------------------------------------------------------
    */
    'attributes' => [
        'name'             => 'الاسم',
        'name_ar'          => 'الاسم بالعربي',
        'phone'            => 'رقم الهاتف',
        'email'            => 'البريد الإلكتروني',
        'password'         => 'كلمة المرور',
        'otp'              => 'رمز التحقق',
        'totp_code'        => 'رمز المصادقة',
        'id_token'         => 'رمز Google',
        'venue_id'         => 'الملعب',
        'booking_id'       => 'الحجز',
        'payment_id'       => 'عملية الدفع',
        'club_id'          => 'النادي',
        'city_id'          => 'المدينة',
        'category_id'      => 'التصنيف',
        'booking_date'     => 'تاريخ الحجز',
        'start_time'       => 'وقت البدء',
        'duration_minutes' => 'المدة',
        'payment_mode'     => 'طريقة الدفع',
        'payment_provider' => 'مزود الدفع',
        'deposit_amount'   => 'مبلغ العربون',
        'confirmed'        => 'التأكيد',
        'rating'           => 'التقييم',
        'query'            => 'نص البحث',
        'date'             => 'التاريخ',
        'preferred_date'   => 'التاريخ المفضل',
        'from_date'        => 'تاريخ البداية',
        'to_date'          => 'تاريخ الانتهاء',
        'is_active'        => 'حالة التفعيل',
        'address'          => 'العنوان',
        'opening_hours'    => 'أوقات العمل',
        'price_per_hour'   => 'السعر بالساعة',
        'reason'           => 'السبب',
        'role'             => 'الدور',
        'avatar'           => 'الصورة الشخصية',
        'sport_ids'        => 'الرياضات',
    ],
];
