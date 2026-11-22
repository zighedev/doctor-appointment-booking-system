<?php

namespace App\Http\Resources\Doctor;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorClinicResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,

            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,

            'consultation_fee' => $this->pivot->consultation_fee,
            'is_relationship_active' => (bool) $this->pivot->is_active,
        ];
    }
}