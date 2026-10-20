<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = ['license_number', 'bio', 'years_of_experience'];

    protected function casts(): array
    {
        return [
            'years_of_experience' => 'integer',
            'is_verified' => 'boolean',
            'is_accepting_appointments' => 'boolean',
        ];
    }

    

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function scheduleExceptions(): HasMany
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function specialties(): BelongsToMany
    {
        return $this->belongsToMany(Specialty::class)
                ->withPivot('is_primary')
                ->withTimestamps();
    }

    public function clinics(): BelongsToMany
    {
        return $this->belongsToMany(Clinic::class)
                ->withPivot(['consultation_fee', 'is_active'])
                ->withTimestamps();
    }

    public function slots(): HasMany
    {
        return $this->hasMany(AppointmentSlot::class);
    }

    public function appointments(): HasManyThrough
    {
        return $this->hasManyThrough(Appointment::class, AppointmentSlot::class);
    }

}
