<?php

namespace App\Http\Resources\Doctor;

use App\Http\Resources\User\UserSummaryResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
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
            'bio' => $this->bio,
            'years_of_experience' => $this->years_of_experience,
            'is_verified' => $this->is_verified,
            'is_accepting_appointments' => $this->is_accepting_appointments,
            
            'user' => new UserSummaryResource($this->whenLoaded('user')),
            'specialties' => DoctorSpecialtyResource::collection($this->whenLoaded('specialties')),
            'clinics' => DoctorClinicResource::collection($this->whenLoaded('clinics')),
        ];
    }
}
