<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }
    

    public function doctors(): BelongsToMany
    {
        return $this->belongsToMany(Doctor::class)
                ->withPivot('is_primary')
                ->withTimestamps();
    }

}
