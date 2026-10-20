<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'rating',
        'comment',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'is_visible' => 'boolean',
        ];
    }



    // Relationships

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }



    // Helper Methods
    
    public function isVisible(): bool
    {
        return $this->is_visible;
    }

}
