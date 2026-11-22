<?php

namespace App\Http\Resources\Doctor;

use App\Http\Resources\User\UserAdminResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorAdminResource extends JsonResource
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
            'user_id' => $this->user_id,
            'license_number' => $this->license_number,
            'bio' => $this->bio,
            'years_of_experience' => $this->years_of_experience,
            'is_verified' => $this->is_verified,
            'is_accepting_appointments' => $this->is_accepting_appointments,

            'user' => new UserAdminResource($this->whenLoaded('user')),
            'specialties' => DoctorSpecialtyAdminResource::collection($this->whenLoaded('specialties')),
            'clinics' => DoctorClinicResource::collection($this->whenLoaded('clinics')),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
