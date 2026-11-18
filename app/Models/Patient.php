<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'blood_type',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'address',
    ];

    

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }


    // Local Scopes

    public function scopeFilterByRegistrationPeriod(Builder $query, string $period): Builder 
    {
        return $query->whereHas('user', function ($query) use ($period) {
            match ($period) {
                'today' => $query->whereDate('created_at', today()),

                'last_7_days' => $query->where(
                    'created_at',
                    '>=',
                    now()->subDays(7)
                ),

                'last_30_days' => $query->where(
                    'created_at',
                    '>=',
                    now()->subDays(30)
                ),

                'this_month' => $query->whereBetween('created_at', [
                    now()->startOfMonth(),
                    now()->endOfMonth(),
                ]),

                default => $query,
            };
        });
    }

    public function scopeFilterByGender(Builder $query, string $gender): Builder
    {
        return $query->whereHas('user', fn ($query) => $query->where('gender', $gender));
    }

    public function scopeFilterByStatus(Builder $query, bool $status): Builder
    {
        return $query->whereHas('user', fn ($query) => $query->where('is_active', $status));
    }

}
