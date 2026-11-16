<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicResource extends JsonResource
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
            'phone' => $this->phone,
            'email' => $this->email,
            'description' => $this->description,
            
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,

            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'timezone' => $this->timezone,

            'booking_confirmation_mode' => $this->booking_confirmation_mode?->value,
            'payment_policy' => $this->payment_policy?->value,
            'reschedule_cutoff_hours' => $this->reschedule_cutoff_hours,
            'confirmation_timeout_minutes' => $this->confirmation_timeout_minutes,
            'payment_timeout_minutes' => $this->payment_timeout_minutes,

            'is_active' => $this->is_active,

            'consultation_fee' => $this->whenPivotLoaded(
                'clinic_doctor',
                fn () => $this->pivot->consultation_fee
            ),

            'is_relationship_active' => $this->whenPivotLoaded(
                'clinic_doctor',
                fn () => (bool) $this->pivot->is_active
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
