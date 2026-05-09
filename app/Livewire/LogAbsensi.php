<?php

namespace App\Livewire;

use App\Models\Absensi;
use Livewire\Component;

class LogAbsensi extends Component
{
    public $filterRole = 'Semua'; 

    public function render()
    {
        $query = Absensi::whereDate('tanggal', today())->orderByDesc('waktu');

        if ($this->filterRole !== 'Semua') {
            $query->where('role_type', $this->filterRole);
        }

        $logs = $query->get();

        return view('livewire.log-absensi', compact('logs'));
    }
}