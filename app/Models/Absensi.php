<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Absensi extends Model
{
    use HasFactory;

    protected $table = 'absensis';

    protected $fillable = [
        'tanggal',      // date
        'waktu',        // time (H:i)
        'nama',         // nama siswa/guru
        'role_type',    // 'Siswa' | 'Guru'
        'status',       // 'Hadir' | 'Izin' | 'Sakit'
        'foto_path',    // path foto (nullable)
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
