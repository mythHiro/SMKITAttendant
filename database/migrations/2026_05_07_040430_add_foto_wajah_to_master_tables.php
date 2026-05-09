<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom foto wajah di tabel siswas
        Schema::table('siswas', function (Blueprint $table) {
            $table->string('foto_wajah')->nullable()->after('jurusan')
                  ->comment('Dataset wajah utama untuk AI');
        });

        // Tambah kolom foto wajah di tabel gurus
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('foto_wajah')->nullable()->after('jabatan')
                  ->comment('Dataset wajah utama untuk AI');
        });
    }

    public function down(): void
    {
        Schema::table('siswas', function (Blueprint $table) {
            $table->dropColumn('foto_wajah');
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropColumn('foto_wajah');
        });
    }
};