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
  .chart-container{width:100%;max-width:400px;margin:0 auto 1rem}
  @media(max-width:768px){.dashboard-grid{grid-template-columns:1fr}}
</style>
@endpush

@section('content')

  {{-- Panggil komponen Livewire untuk angka yang auto-update --}}
  <livewire:dashboard-stats />

@endsection

@push('scripts')
<script>
// Fungsi ini tetap kita pakai untuk menampilkan popup daftar nama
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
</script>
@endpush
