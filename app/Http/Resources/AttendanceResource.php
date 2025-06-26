<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id'         => $this->id,
            'user_id'    => $this->user_id,
            'user'       => $this->whenLoaded('user', function () {
                return new UserResource($this->user);
            }),
            'date'       => $this->date,
            'clock_in'   => $this->clock_in,
            'clock_out'  => $this->clock_out,
            'is_permission' => $this->is_permission ?? false,
            'permission_type' => $this->permission_type ?? null,
        ];
    }
}
