<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Guru extends Model
{
    use HasFactory;

    protected $table = 'gurus';

    protected $fillable = ['nip', 'nama', 'jabatan', 'foto_wajah'];

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'nama', 'nama')
            ->where('role_type', 'Guru');
    }

    public function kehadiranHariIni(): ?string
    {
        return $this->absensis()
            ->whereDate('tanggal', today())
            ->value('status');
    }
}
