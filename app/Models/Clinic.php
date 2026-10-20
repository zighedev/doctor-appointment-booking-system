<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\BookingConfirmationMode;
use App\Enums\PaymentPolicy;

class Clinic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'description',
        'address',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'timezone',
        'booking_confirmation_mode',
        'payment_policy',
        'reschedule_cutoff_hours',
        'confirmation_timeout_minutes',
        'payment_timeout_minutes',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'booking_confirmation_mode' => BookingConfirmationMode::class,
            'payment_policy' => PaymentPolicy::class,
            'reschedule_cutoff_hours' => 'integer',
            'confirmation_timeout_minutes' => 'integer',
            'payment_timeout_minutes' => 'integer',
            'is_active' => 'boolean',
        ];
    }

   

    // Relationships

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class)
                ->withPivot(['consultation_fee', 'is_active'])
                ->withTimestamps();
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(ClinicStaff::class);
    }

}
