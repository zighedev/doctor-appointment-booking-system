<?php

namespace App\Enums;

enum AppointmentSlotStatus: string
{
    case AVAILABLE = 'available';
    case BOOKED = 'booked';
    case BLOCKED = 'blocked';


    public function isAvailable(): bool 
    { 
        return $this === self::AVAILABLE; 
    } 
    
    public function isBooked(): bool 
    { 
        return $this === self::BOOKED; 
    } 

    public function isBlocked(): bool 
    { 
        return $this === self::BLOCKED; 
    }
}

