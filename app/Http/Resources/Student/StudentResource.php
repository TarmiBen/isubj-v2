<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Student */
class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_number' => $this->student_number,
            'name' => $this->name,
            'last_name1' => $this->last_name1,
            'last_name2' => $this->last_name2,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'curp' => $this->curp,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth?->format('Y-m-d'),
            'street' => $this->street,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postal_code,
            'country' => $this->country,
            'status' => $this->status,
            'generation' => $this->whenLoaded('generation', fn () => $this->generation ? trim(($this->generation->career?->name ?? '').' - Gen. '.$this->generation->number) : null),
            'photo_url' => $this->photo ? Storage::disk('public')->url($this->photo) : null,
            'photo_thumb_url' => $this->photo_thumb ? Storage::disk('public')->url($this->photo_thumb) : null,
            'pending_balance' => (float) $this->pending_balance,
        ];
    }
}
