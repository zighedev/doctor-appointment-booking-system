<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppointmentRescheduling extends Model
{
    use HasFactory;

    protected $fillable = ['reason'];

    

    // Relationships

     public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function oldSlot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class);
    }

    public function newSlot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

}
