<?php

namespace App\Exports;

use App\Models\Absensi;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class AbsensiExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    /**
    * Mengambil semua data absensi diurutkan dari yang terbaru
    */
    public function collection()
    {
        return Absensi::orderBy('tanggal', 'desc')->orderBy('waktu', 'desc')->get();
    }

    /**
    * Menentukan judul kolom (Header) di file Excel
    */
    public function headings(): array
    {
        return [
            'Tanggal',
            'Waktu',
            'Nama',
            'Jabatan/Status',
            'Kehadiran'
        ];
    }

    /**
    * Memetakan data dari database ke kolom Excel
    */
    public function map($absensi): array
    {
        return [
            $absensi->tanggal->format('d/m/Y'), // Format tanggal rapi
            $absensi->waktu,
            $absensi->nama,
            $absensi->role_type,
            $absensi->status,
        ];
    }
}