<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard');
    }

    /** GET /dashboard/stats — JSON untuk counter cards */
    /** GET /dashboard/stats — JSON untuk counter cards */
    public function stats()
    {
        $today = today()->toDateString();

        $rekap = Absensi::where('tanggal', $today)
            ->selectRaw('role_type, status, count(*) as total')
            ->groupBy('role_type', 'status')
            ->get();

        $build = function (string $roleType) use ($rekap) {
            $dataRole = $rekap->where('role_type', $roleType)->pluck('total', 'status');
            
            return [
                'hadir' => $dataRole->get('Hadir', 0),
                'izin'  => $dataRole->get('Izin', 0),
                'sakit' => $dataRole->get('Sakit', 0),
            ];
        };

        return response()->json([
            'siswa' => $build('Siswa'),
            'guru'  => $build('Guru'),
        ]);
    }

    /** GET /dashboard/detail?role=Siswa&status=Hadir */
    public function detail(Request $request)
    {
        $request->validate([
            'role'   => 'required|in:Siswa,Guru',
            'status' => 'required|in:Hadir,Izin,Sakit',
        ]);

        $logs = Absensi::whereDate('tanggal', today())
            ->where('role_type', $request->role)
            ->where('status', $request->status)
            ->orderBy('waktu')
            ->get(['nama', 'waktu']);

        return response()->json($logs);
    }
}
