<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\AppointmentStatus;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'status',
        'reason',
        'patient_notes',
        'doctor_notes',
        'cancellation_reason',
        'rejection_reason',
        'expires_at',
        'confirmed_at',
        'cancelled_at',
        'checked_in_at',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AppointmentStatus::class,
            'expires_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'checked_in_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }


    // Relationships

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(Review::class);
    }

    public function appointmentSlot(): BelongsTo
    {
        return $this->belongsTo(AppointmentSlot::class);
    }

    public function reschedulings(): HasMany
{
    return $this->hasMany(AppointmentRescheduling::class);
}



    // Helper Methods

    public function isPending(): bool
    {
        return $this->status === AppointmentStatus::PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === AppointmentStatus::CONFIRMED;
    }

    public function isCancelled(): bool
    {
        return $this->status === AppointmentStatus::CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->status === AppointmentStatus::COMPLETED;
    }

    public function isNoShow(): bool
    {
        return $this->status === AppointmentStatus::NO_SHOW;
    }

    public function isRejected(): bool
    {
        return $this->status === AppointmentStatus::REJECTED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            AppointmentStatus::PENDING,
            AppointmentStatus::AWAITING_PAYMENT,
            AppointmentStatus::CONFIRMED], true);
    }

    public function canBeConfirmed(): bool
    {
        return $this->isPending();
    }    

}
