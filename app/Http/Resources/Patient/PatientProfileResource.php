<?php

namespace App\Http\Resources\Patient;

use App\Http\Resources\User\UserProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientProfileResource extends JsonResource
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
            'blood_type' => $this->blood_type,
            'emergency_contact_name' => $this->emergency_contact_name,
            'emergency_contact_phone' => $this->emergency_contact_phone,
            'emergency_contact_relationship' => $this->emergency_contact_relationship,
            'address' => $this->address,

            'user' => new UserProfileResource($this->whenLoaded('user')),
            
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            
        ];
    }
}
