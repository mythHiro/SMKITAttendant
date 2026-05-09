@extends('layouts.app')

@section('title', 'Perangkat IoT')
@section('page_title', 'Manajemen Perangkat IoT')
@section('nav_device', 'active')

@section('content')
<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-microchip"></i> Data Perangkat Kamera / IoT</h2>
  </div>
  <div class="card-body">
    <p style="color:#555;margin-bottom:1.5rem;">
      Kelola perangkat IoT yang diizinkan untuk mengirim data absensi ke server. Copy <strong>API Key</strong> di bawah ini dan masukkan ke dalam header <code>X-Device-Key</code> pada script Raspberry Pi Anda.
    </p>

    {{-- Panggil komponen Livewire --}}
    <livewire:device-manager />

  </div>
</div>
@endsection