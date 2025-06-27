<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    public function toArray($request)
{
    return [
        'id' => $this->id,
        'user' => new UserResource($this->user),
        'clock_in' => $this->clock_in,
        'clock_out' => $this->clock_out,
        'date' => $this->date,
        'is_permission' => (bool) $this->is_permission,
        'permission_type' => $this->permission_type,
    ];
}


}
