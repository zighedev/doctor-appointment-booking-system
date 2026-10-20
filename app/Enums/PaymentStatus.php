<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case CANCELLED = 'cancelled';


    public function isSuccessful(): bool 
    { 
        return $this === self::PAID; 
    } 

    public function canRetry(): bool 
    { 
        return in_array($this, [self::FAILED, self::CANCELLED], true); 
    }
    
}
