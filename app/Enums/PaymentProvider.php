<?php

namespace App\Enums;

enum PaymentProvider: string
{
    case SyriatelCash = 'syriatel_cash';
    case MtnCash = 'mtn_cash';
    case Fatora = 'fatora';
    case SamaPay = 'sama_pay';
    case Wallet = 'wallet';
}
