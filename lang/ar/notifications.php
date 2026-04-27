<?php

return [
    'types' => [
        'booking_confirmed' => 'تأكيد الحجز',
        'booking_reminder' => 'تذكير بالحجز',
        'booking_cancelled' => 'إلغاء الحجز',
        'booking_completed' => 'اكتمال الحجز',
        'payment_received' => 'استلام الدفع',
        'payment_failed' => 'فشل الدفع',
        'refund_processed' => 'استرداد الدفع',
        'promo_available' => 'عرض متاح',
        'promo_expiring' => 'عرض على وشك الانتهاء',
        'review_request' => 'طلب تقييم',
        'review_helpful' => 'تقييم مفيد',
        'system_announcement' => 'إعلان النظام',
        'venue_added' => 'ملعب جديد',
    ],

    'booking_confirmed' => [
        'title' => 'تم تأكيد الحجز',
        'body' => 'تم تأكيد حجزك في :venue بتاريخ :date',
    ],
    'booking_reminder' => [
        'title' => 'تذكير بالحجز',
        'body' => 'لديك حجز غداً في :venue الساعة :time',
    ],
    'booking_cancelled' => [
        'title' => 'تم إلغاء الحجز',
        'body' => 'تم إلغاء حجزك في :venue',
    ],
    'booking_completed' => [
        'title' => 'اكتمل الحجز',
        'body' => 'نشكرك لاستخدامك دق احجزلي',
    ],
    'payment_received' => [
        'title' => 'تم استلام الدفع',
        'body' => 'تم استلام دفعة بقيمة :amount ل.س بنجاح',
    ],
    'payment_failed' => [
        'title' => 'فشل الدفع',
        'body' => 'تعذرت معالجة دفعتك. يرجى المحاولة مرة أخرى',
    ],
    'refund_processed' => [
        'title' => 'تم الاسترداد',
        'body' => 'تم استرداد مبلغ :amount ل.س إلى محفظتك',
    ],
    'review_request' => [
        'title' => 'قيّم تجربتك',
        'body' => 'شاركنا تجربتك في :venue',
    ],
    'review_helpful' => [
        'title' => 'تقييمك أعجب مستخدماً آخر',
        'body' => 'وجد أحد المستخدمين تقييمك مفيداً',
    ],
    'promo_available' => [
        'title' => 'عرض جديد',
        'body' => 'احصل على خصم :discount% على حجزك التالي',
    ],
    'promo_expiring' => [
        'title' => 'عرض على وشك الانتهاء',
        'body' => 'سارع باستخدام كود الخصم قبل انتهاء العرض',
    ],
    'system_announcement' => [
        'title' => 'إعلان',
        'body' => '',
    ],
    'venue_added' => [
        'title' => 'ملعب جديد',
        'body' => 'تمت إضافة ملعب جديد بالقرب منك',
    ],

    'default' => [
        'title' => 'إشعار جديد',
    ],
];
