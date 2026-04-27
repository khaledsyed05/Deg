<?php

namespace App\Enums;

enum CreditType: string
{
    case TOPUP = 'topup';
    case PROMOTIONAL = 'promotional';
    case REFERRAL = 'referral';
    case REFUND = 'refund';
    case TRANSFER_IN = 'transfer_in';
    case TRANSFER_OUT = 'transfer_out';
    case BOOKING = 'booking';
    case WITHDRAWAL = 'withdrawal';
    case BONUS = 'bonus';
    case EVENT_REGISTRATION = 'event_registration';

    public function label(): string
    {
        return match ($this) {
            self::TOPUP => 'شحن رصيد',
            self::PROMOTIONAL => 'رصيد ترويجي',
            self::REFERRAL => 'مكافأة إحالة',
            self::REFUND => 'استرجاع',
            self::TRANSFER_IN => 'تحويل وارد',
            self::TRANSFER_OUT => 'تحويل صادر',
            self::BOOKING => 'حجز',
            self::WITHDRAWAL => 'سحب',
            self::BONUS => 'مكافأة',
            self::EVENT_REGISTRATION => 'تسجيل في فعالية',
        };
    }

    public function isCredit(): bool
    {
        return in_array($this, [
            self::TOPUP,
            self::PROMOTIONAL,
            self::REFERRAL,
            self::REFUND,
            self::TRANSFER_IN,
            self::BONUS,
        ], true);
    }

    public function isDebit(): bool
    {
        return in_array($this, [
            self::BOOKING,
            self::TRANSFER_OUT,
            self::WITHDRAWAL,
            self::EVENT_REGISTRATION,
        ], true);
    }
}
