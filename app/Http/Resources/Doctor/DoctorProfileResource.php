<?php

namespace App\Http\Resources\Doctor;

use App\Http\Resources\User\UserProfileResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorProfileResource extends JsonResource
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
            'license_number' => $this->license_number,
            'bio' => $this->bio,
            'years_of_experience' => $this->years_of_experience,
            'is_verified' => $this->is_verified,
            'is_accepting_appointments' => $this->is_accepting_appointments,

            'user' => new UserProfileResource($this->whenLoaded('user')),
            'specialties' => DoctorSpecialtyResource::collection($this->whenLoaded('specialties')),
            'clinics' => DoctorClinicResource::collection($this->whenLoaded('clinics')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
