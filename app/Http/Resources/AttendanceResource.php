<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource {
    public function toArray($request) {
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'date' => $this->date,
            'clock_in' => $this->clock_in,
            'clock_out' => $this->clock_out,
        ];
    }
}
