@extends('layouts.app')

@section('title', 'Laporan')
@section('page_title', 'Laporan Kehadiran')
@section('nav_laporan', 'active')

@section('content')
<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-file-excel"></i> Laporan Kehadiran</h2>
  </div>
  <div class="card-body">
    <p style="color:#555;margin-bottom:1.5rem;">
      Download rekap absensi seluruh data ke file <strong>Microsoft Excel (.xlsx)</strong>. File ini sudah diformat secara otomatis agar mudah dibaca dan dicetak.
    </p>

    <a href="{{ route('laporan.export') }}" class="btn btn-success" style="background: #217346; border-color: #217346;">
      <i class="fas fa-file-excel"></i> Unduh File Excel
    </a>
  </div>
</div>
@endsection