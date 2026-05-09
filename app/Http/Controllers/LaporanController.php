<?php

namespace App\Http\Controllers;

use App\Exports\AbsensiExport;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    public function index()
    {
        return view('laporan.index');
    }

    public function export()
    {
        $filename = 'Laporan_Absensi_' . now()->format('Ymd_His') . '.xlsx';
        
        // Gunakan facade Excel untuk mendownload file
        return Excel::download(new AbsensiExport, $filename);
    }
}