<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAdminResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [ 
            'id' => $this->id, 
            'name' => $this->name, 
            'email' => $this->email, 
            'phone' => $this->phone, 
            'gender' => $this->gender?->value, 
            'date_of_birth' => $this->date_of_birth?->toDateString(), 
            'role' => $this->role?->value,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
