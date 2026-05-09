<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Siswa extends Model
{
    use HasFactory;

    protected $table = 'siswas';

    protected $fillable = ['nis', 'nama', 'kelas', 'jurusan', 'foto_wajah'];

    public function absensis()
    {
        return $this->hasMany(Absensi::class, 'nama', 'nama')
            ->where('role_type', 'Siswa');
    }

    public function kehadiranHariIni(): ?string
    {
        return $this->absensis()
            ->whereDate('tanggal', today())
            ->value('status');
    }
}
