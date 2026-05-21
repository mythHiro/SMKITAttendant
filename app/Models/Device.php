<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['name', 'api_key', 'is_active', 'last_seen'];

    // Pastikan Laravel mengenali last_seen sebagai format tanggal (Carbon)
    protected $casts = [
        'last_seen' => 'datetime',
    ];
}