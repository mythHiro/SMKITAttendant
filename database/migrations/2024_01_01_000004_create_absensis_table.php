<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensis', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('waktu', 5);                // HH:mm
            $table->string('nama', 100);
            $table->enum('role_type', ['Siswa', 'Guru']);
            $table->enum('status', ['Hadir', 'Izin', 'Sakit'])->default('Hadir');
            $table->string('foto_path')->nullable();
            $table->timestamps();

            // Satu orang hanya bisa absen sekali per hari
            $table->unique(['tanggal', 'nama', 'role_type'], 'uq_absensi_harian');

            $table->index(['tanggal', 'role_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensis');
    }
};
