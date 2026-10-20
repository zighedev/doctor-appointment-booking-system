<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\ScheduleExceptionType;

class ScheduleException extends Model
{
    use HasFactory;

    protected $fillable = [
        'exception_date',
        'type',
        'start_time',
        'end_time',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'exception_date' => 'date',
            'type' => ScheduleExceptionType::class,
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



    // Helper Methods

    public function isClosed(): bool
    {
        return $this->type === ScheduleExceptionType::CLOSED;
    }

    public function hasSpecialHours(): bool
    {
        return $this->type === ScheduleExceptionType::SPECIAL_HOURS;
    }

}
