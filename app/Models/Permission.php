<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'user_id', 'date', 'type', 'description', 'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}