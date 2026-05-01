<?php

return [
    'types' => [
        'booking_confirmed' => 'تأكيد الحجز',
        'booking_reminder' => 'تذكير بالحجز',
        'booking_cancelled' => 'إلغاء الحجز',
        'booking_completed' => 'اكتمال الحجز',
        'booking_rescheduled' => 'تعديل موعد الحجز',
        'payment_received' => 'استلام الدفع',
        'payment_failed' => 'فشل الدفع',
        'payment_retry' => 'إعادة محاولة الدفع',
        'refund_processed' => 'استرداد الدفع',
        'refund_completed' => 'اكتمال الاسترداد',
        'settlement_paid' => 'تسوية مدفوعة',
        'promo_available' => 'عرض متاح',
        'promo_expiring' => 'عرض على وشك الانتهاء',
        'review_request' => 'طلب تقييم',
        'review_helpful' => 'تقييم مفيد',
        'system_announcement' => 'إعلان النظام',
        'venue_added' => 'ملعب جديد',
        'club_approved' => 'تمت الموافقة على النادي',
        'club_rejected' => 'تم رفض طلب النادي',
        'waitlist_available' => 'الوقت المطلوب متاح',
        'goal_scored' => 'هدف!',
        'match_result' => 'نتيجة المباراة',
        'match_reminder' => 'تذكير بمباراة',
        'daily_match_summary' => 'ملخص مباريات اليوم',
        'event_reminder' => 'تذكير بفعالية',
        'achievement_unlocked' => 'إنجاز جديد',
        'emergency_reported' => 'بلاغ طوارئ',
        'group_booking_invite' => 'دعوة لحجز جماعي',
        'team_invitation' => 'دعوة لفريق',
    ],

    'booking_confirmed' => [
        'title' => 'تم تأكيد الحجز',
        'body' => 'تم تأكيد حجزك في :venue بتاريخ :date',
    ],
    'booking_reminder' => [
        'title' => 'تذكير بالحجز',
        'body' => 'لديك حجز قريب في :venue الساعة :time',
    ],
    'booking_cancelled' => [
        'title' => 'تم إلغاء الحجز',
        'body' => 'تم إلغاء حجزك في :venue',
    ],
    'booking_completed' => [
        'title' => 'اكتمل الحجز',
        'body' => 'نشكرك لاستخدامك دق احجزلي',
    ],
    'booking_rescheduled' => [
        'title' => 'تم تعديل موعد الحجز',
        'body' => 'تم تغيير موعد حجزك في :venue',
    ],
    'payment_received' => [
        'title' => 'تم استلام الدفع',
        'body' => 'تم استلام دفعة بقيمة :amount ل.س بنجاح',
    ],
    'payment_failed' => [
        'title' => 'فشل الدفع',
        'body' => 'تعذرت معالجة دفعتك. يرجى المحاولة مرة أخرى',
    ],
    'payment_retry' => [
        'title' => 'إعادة محاولة الدفع',
        'body' => 'تم إعادة محاولة دفعتك بقيمة :amount ل.س',
    ],
    'refund_processed' => [
        'title' => 'تم الاسترداد',
        'body' => 'تم استرداد مبلغ :amount ل.س إلى محفظتك',
    ],
    'refund_completed' => [
        'title' => 'اكتمل الاسترداد',
        'body' => 'تم استرداد مبلغ :amount ل.س بنجاح',
    ],
    'settlement_paid' => [
        'title' => 'تم دفع التسوية',
        'body' => 'تم تحويل تسويتك بقيمة :amount ل.س',
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
    'club_approved' => [
        'title' => 'تمت الموافقة على ناديك ✅',
        'body' => 'تمت الموافقة على :club وأصبح متاحاً للحجوزات.',
    ],
    'club_rejected' => [
        'title' => 'تم رفض طلب ناديك',
        'body' => 'تم رفض طلب :club. السبب: :reason',
    ],
    'waitlist_available' => [
        'title' => 'الوقت المطلوب أصبح متاحاً!',
        'body' => 'الوقت الذي طلبت إشعارك عنه في :date الساعة :time أصبح متاحاً للحجز.',
    ],
    'goal_scored' => [
        'title' => '⚽ هدف! :team',
        'body' => ':home :score :away (:minute)',
    ],
    'match_result' => [
        'title' => '🏁 نتيجة المباراة',
        'body' => ':home :score :away',
    ],
    'match_reminder' => [
        'title' => '🏟️ تذكير بمباراة',
        'body' => ':home ضد :away',
    ],
    'daily_match_summary' => [
        'title' => '📅 ملخص مباريات اليوم',
        'body' => 'لديك :count مباراة لفرقك المفضلة اليوم',
    ],
    'event_reminder' => [
        'title' => 'تذكير بفعالية',
        'body' => 'تبدأ :event :window',
    ],
    'achievement_unlocked' => [
        'title' => 'إنجاز جديد! 🏆',
        'body' => 'حصلت على إنجاز: :achievement',
    ],
    'emergency_reported' => [
        'title' => '🚨 بلاغ طوارئ',
        'body' => 'تم الإبلاغ عن حالة طوارئ بدرجة :severity',
    ],
    'group_booking_invite' => [
        'title' => 'دعوة لحجز جماعي',
        'body' => ':inviter دعاك للانضمام إلى حجز في :venue',
    ],
    'team_invitation' => [
        'title' => 'دعوة لفريق',
        'body' => ':inviter دعاك للانضمام إلى فريق :team',
    ],

    'default' => [
        'title' => 'إشعار جديد',
    ],
];
