<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Menambahkan 'jabatan' ke dalam daftar tipe kategori yang diizinkan
        DB::statement("ALTER TABLE kategoris MODIFY COLUMN tipe ENUM('kelas', 'jurusan', 'jabatan') NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE kategoris MODIFY COLUMN tipe ENUM('kelas', 'jurusan') NOT NULL");
    }
};