<?php

namespace App\Enums;

enum PaymentFlowType: string
{
    case Otp = 'otp';
    case Webview = 'webview';
    case Internal = 'internal';
}
