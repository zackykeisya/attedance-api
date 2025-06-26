<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Permission extends Model
{
    use SoftDeletes; // Aktifkan fitur soft delete

    public $incrementing = false; // Penting untuk UUID
    protected $keyType = 'string'; // UUID adalah string

    protected $fillable = [
        'id',
        'user_id',
        'date',
        'type',
        'description',
        'status'
    ];

    protected static function boot()
    {
        parent::boot();

        // Auto-generate UUID saat create jika belum diisi
        static::creating(function ($model) {
            if (empty($model->id)) {
                $model->id = (string) Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
