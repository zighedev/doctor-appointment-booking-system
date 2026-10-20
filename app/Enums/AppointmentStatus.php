<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case PENDING = 'pending';
    case AWAITING_PAYMENT = 'awaiting_payment';
    case CONFIRMED = 'confirmed';

    case CHECKED_IN = 'checked_in';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';

    case CANCELLED = 'cancelled';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case NO_SHOW = 'no_show';


    public function isFinal(): bool
    {
        return match ($this) {
            self::COMPLETED,
            self::CANCELLED,
            self::REJECTED,
            self::EXPIRED,
            self::NO_SHOW => true,
            default => false,
        };
    }

    public function canTransitionTo(self $next): bool
    {
        return in_array($next, match ($this) {
            self::PENDING => [self::CONFIRMED, self::AWAITING_PAYMENT, self::CANCELLED, self::REJECTED, self::EXPIRED],
            self::AWAITING_PAYMENT => [self::CONFIRMED, self::CANCELLED, self::EXPIRED],
            self::CONFIRMED => [self::CHECKED_IN, self::CANCELLED, self::NO_SHOW],
            self::CHECKED_IN => [self::IN_PROGRESS],
            self::IN_PROGRESS => [self::COMPLETED],
            default => [],
        }, true);   
    }
    
}
