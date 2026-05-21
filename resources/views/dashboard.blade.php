@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('nav_dashboard', 'active')

@push('styles')
<style>
  .dashboard-grid{display:grid;grid-template-columns:1fr 1fr;gap:2rem}
  .role-section h3{font-size:1rem;font-weight:600;color:#777;margin-bottom:.75rem;display:flex;align-items:center;gap:.4rem}
  .role-section{display:flex;flex-direction:column;gap:.75rem}
  .dash-card{background:#fff;border-radius:12px;padding:1.2rem;box-shadow:0 4px 12px rgba(0,0,0,.04);display:flex;flex-direction:column;gap:.3rem;cursor:pointer;transition:transform .1s,box-shadow .1s;border:none;text-align:left;width:100%}
  .dash-card:hover{transform:translateY(-2px);box-shadow:0 6px 16px rgba(0,0,0,.08)}
  .dash-icon{font-size:1.8rem;color:#4A90E2}
  .dash-value{font-size:2rem;font-weight:700}
  .dash-label{font-size:.85rem;color:#777}
  
  /* Styling untuk container chart */
  .chart-container{position:relative; width:100%; height:300px; margin:0 auto}
  .dashboard-wrapper { display: flex; flex-direction: column; gap: 2rem; }
  
  @media(max-width:768px){.dashboard-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')
  <div class="dashboard-wrapper">
    
    {{-- Panggil komponen Livewire HANYA SEKALI di sini --}}
    <livewire:dashboard-stats />

    {{-- KARTU CHART BARU --}}
    @if(auth()->user()->isAdmin())
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-chart-pie"></i> Persentase Kehadiran Hari Ini</h2>
        </div>
        <div class="card-body">
            <div class="chart-container">
                <canvas id="attendanceChart"></canvas>
            </div>
        </div>
    </div>
    @endif

  </div>
@endsection

@push('scripts')
<script>
// 1. Fungsi Popup Detail (Tetap seperti sebelumnya)
async function showDetail(roleLabel, status) {
  const res  = await fetch(`{{ route('dashboard.detail') }}?role=${roleLabel}&status=${status}`, { headers: { Accept: 'application/json' } });
  const rows = await res.json();

  const tableRows = rows.length
    ? rows.map(r => `<tr><td>${r.nama}</td><td>${r.waktu}</td></tr>`).join('')
    : `<tr><td colspan="2" style="text-align:center;color:#aaa;">Belum ada data.</td></tr>`;

  openModal(`
    <h3>${roleLabel} – ${status} (Hari Ini)</h3>
    <table class="simple-table">
      <thead><tr><th>Nama</th><th>Waktu</th></tr></thead>
      <tbody>${tableRows}</tbody>
    </table>
    <div class="modal-buttons">
      <button class="btn btn-primary btn-sm" onclick="closeModal()">Tutup</button>
    </div>`);
}

// 2. Fungsi Render Chart.js
document.addEventListener('DOMContentLoaded', async function() {
    const canvas = document.getElementById('attendanceChart');
    if(!canvas) return; // Mencegah error jika login sebagai siswa (canvas disembunyikan)
    
    const ctx = canvas.getContext('2d');
    
    try {
        // Ambil data dari API stats
        const res = await fetch('{{ route("dashboard.stats") }}');
        const data = await res.json();

        // Inisialisasi Chart.js
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Hadir', 'Izin', 'Sakit'],
                datasets: [{
                    // Menjumlahkan data Siswa + Guru
                    data: [
                        (data.siswa.hadir || 0) + (data.guru.hadir || 0), 
                        (data.siswa.izin || 0) + (data.guru.izin || 0), 
                        (data.siswa.sakit || 0) + (data.guru.sakit || 0)
                    ],
                    backgroundColor: ['#16A34A', '#D97706', '#DC2626'], // Hijau, Oranye, Merah
                    borderWidth: 0,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20,
                            font: { family: 'DM Sans', size: 12 }
                        }
                    }
                },
                cutout: '75%' // Ukuran lubang donat
            }
        });
    } catch (error) {
        console.error("Gagal memuat data chart:", error);
    }
});
</script>
@endpush