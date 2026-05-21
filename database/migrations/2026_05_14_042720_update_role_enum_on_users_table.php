<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan 'guru' ke dalam daftar ENUM
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'siswa', 'guru') NOT NULL DEFAULT 'siswa'");
    }

    public function down(): void
    {
        // Kembalikan ke semula jika di-rollback
        DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'siswa') NOT NULL DEFAULT 'siswa'");
    }
};