<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Absensi;

class DashboardStats extends Component
{
    public function render()
    {
        $user = auth()->user();

        if ($user->role === 'siswa') {
            // Ambil total akumulasi untuk kartu
            $rekap = Absensi::where('nama', $user->name)
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');

            $stats = [
                'hadir' => $rekap->get('Hadir', 0),
                'izin'  => $rekap->get('Izin', 0),
                'sakit' => $rekap->get('Sakit', 0),
            ];
            
            // Ambil 10 riwayat absensi terbaru milik pribadi
            $logs = Absensi::where('nama', $user->name)
                ->orderBy('tanggal', 'desc')
                ->orderBy('waktu', 'desc')
                ->take(10)
                ->get();

            $mode = 'siswa';
        } else {
            // MODE ADMIN (Tetap seperti sebelumnya)
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

            $stats = [
                'siswa' => $build('Siswa'),
                'guru'  => $build('Guru'),
            ];
            
            $logs = collect(); // Admin tidak butuh variabel logs ini di sini
            $mode = 'admin';
        }

        return view('livewire.dashboard-stats', compact('stats', 'mode', 'logs'));
    }
}