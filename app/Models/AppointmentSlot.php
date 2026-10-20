<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\AppointmentStatus;
use App\Enums\AppointmentSlotStatus;

class AppointmentSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'slot_date',
        'start_time',
        'end_time',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'slot_date' => 'date',
            'status' => AppointmentSlotStatus::class,
        ];
    }



    // Relationships

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    public function oldReschedulings(): HasMany
    {
        return $this->hasMany(AppointmentRescheduling::class, 'old_slot_id');
    }

    public function newReschedulings(): HasMany
    {
        return $this->hasMany(AppointmentRescheduling::class, 'new_slot_id');
    }



    // Helper Methods
    public function isAvailable(): bool
    {
        return $this->status === AppointmentSlotStatus::AVAILABLE;
    }

    public function isBooked(): bool
    {
        return $this->status === AppointmentSlotStatus::BOOKED;
    }

    public function isBlocked(): bool
    {
        return $this->status === AppointmentSlotStatus::BLOCKED;
    }

    public function canBeBooked(): bool
    {
        if (! $this->isAvailable()) {
            return false;
        }

        return ! $this->appointment()
            ->whereIn('status', [
                AppointmentStatus::PENDING->value,
                AppointmentStatus::AWAITING_PAYMENT->value,
                AppointmentStatus::CONFIRMED->value,
                AppointmentStatus::CHECKED_IN->value,
                AppointmentStatus::IN_PROGRESS->value,
            ])
            ->exists();
    }

}
