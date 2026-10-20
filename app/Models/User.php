<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Enums\UserGender;
use App\Enums\UserRole;

#[Fillable(['name', 'email', 'phone', 'password', 'gender', 'date_of_birth'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'gender' => UserGender::class,
            'date_of_birth' => 'date',
            'is_active' => 'boolean',
        ];
    }


    // Relationships

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function clinicStaff(): HasMany
    {
        return $this->hasMany(ClinicStaff::class);
    }

    public function cancelledAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'cancelled_by_id');
    }

    public function checkedInAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'checked_in_by_id');
    }

    public function appointmentReschedulings(): HasMany
    {
        return $this->hasMany(AppointmentRescheduling::class, 'changed_by_id');
    }



    // Helper Methods

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isDoctor(): bool
    {
        return $this->role === UserRole::DOCTOR;
    }

    public function isPatient(): bool
    {
        return $this->role === UserRole::PATIENT;
    }

    public function isReceptionist(): bool
    {
        return $this->role === UserRole::RECEPTIONIST;
    }

}
