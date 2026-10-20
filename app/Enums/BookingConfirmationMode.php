<?php

namespace App\Enums;

enum BookingConfirmationMode: string
{
    case AUTO_CONFIRM = 'auto_confirm';
    case MANUAL_CONFIRMATION = 'manual_confirmation';
}
