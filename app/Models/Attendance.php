<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Attendance extends Model {
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'clock_in',
        'clock_out',
        'date',
        'is_permission',
        'permission_type' // 🛠️ Tambahkan field ini
    ];

    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            $model->id = Str::uuid()->toString();
        });
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
}
