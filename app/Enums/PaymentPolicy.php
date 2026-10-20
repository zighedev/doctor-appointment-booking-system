<?php

namespace App\Enums;

enum PaymentPolicy: string
{
    case PAY_AT_CLINIC = 'pay_at_clinic';
    case ONLINE_ONLY = 'online_only';
    case ONLINE_OR_CLINIC = 'online_or_clinic';
}
