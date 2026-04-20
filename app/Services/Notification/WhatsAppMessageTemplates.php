<?php

namespace App\Services\Notification;

use App\Models\Booking;

class WhatsAppMessageTemplates
{
    public static function bookingConfirmed(Booking $booking): string
    {
        $venueName = $booking->venue->getTranslation('name', 'ar');
        $date = $booking->booking_date->format('Y-m-d');

        return <<<MSG
        ✅ *تأكيد الحجز*

        مرحباً {$booking->user->name}،

        تم تأكيد حجزك في {$venueName}

        📅 التاريخ: {$date}
        ⏰ الوقت: {$booking->start_time}
        💰 المبلغ المدفوع: {$booking->deposit_amount} ل.س
        💵 المبلغ المتبقي: {$booking->remaining_amount} ل.س (يُدفع عند الوصول)

        كود الحجز: *{$booking->booking_code}*

        نتمنى لك تجربة ممتعة! ⚽
        MSG;
    }

    public static function paymentReminder(Booking $booking): string
    {
        $venueName = $booking->venue->getTranslation('name', 'ar');
        $date = $booking->booking_date->format('Y-m-d');

        return <<<MSG
        ⏰ *تذكير بالدفع*

        مرحباً {$booking->user->name}،

        حجزك في {$venueName} بانتظار الدفع.

        📅 التاريخ: {$date}
        ⏰ الوقت: {$booking->start_time}
        💰 المبلغ المطلوب: {$booking->deposit_amount} ل.س

        كود الحجز: *{$booking->booking_code}*

        يرجى إتمام الدفع في أقرب وقت.
        MSG;
    }

    public static function bookingCancelled(Booking $booking, string $reason = ''): string
    {
        $venueName = $booking->venue->getTranslation('name', 'ar');
        $date = $booking->booking_date->format('Y-m-d');
        $reasonText = $reason ? "\n        السبب: {$reason}" : '';

        return <<<MSG
        ❌ *إلغاء الحجز*

        مرحباً {$booking->user->name}،

        تم إلغاء حجزك في {$venueName}.

        📅 التاريخ: {$date}
        ⏰ الوقت: {$booking->start_time}
        كود الحجز: *{$booking->booking_code}*{$reasonText}

        سيتم إعادة المبلغ المدفوع خلال 3-5 أيام عمل.
        MSG;
    }
}
