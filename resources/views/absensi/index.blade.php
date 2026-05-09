@extends('layouts.app')

@section('title', 'Absensi')
@section('page_title', 'Absensi Hari Ini')
@section('nav_absensi', 'active')

@push('styles')
<style>
  .camera-area{position:relative;max-width:480px;margin:0 auto 1.5rem;border:2px dashed #DDD;border-radius:12px;overflow:hidden;background:#FAFAFA;display:flex;align-items:center;justify-content:center;min-height:160px}
  .camera-area video{width:100%;height:auto;display:none}
  .camera-placeholder{display:flex;flex-direction:column;align-items:center;gap:.5rem;color:#AAA;padding:2rem}
  .camera-placeholder i{font-size:2.5rem}
  .preview-area{text-align:center;margin-top:1rem}
  .preview-area img{max-width:200px;border-radius:8px;margin-top:.5rem}
  .btn-row{display:flex;gap:.75rem;flex-wrap:wrap;margin-top:1rem}
</style>
@endpush

@section('content')

{{-- ── Presensi Wajah ── --}}
<div class="card">
  <div class="card-header"><h2><i class="fas fa-camera"></i> Presensi Wajah</h2></div>
  <div class="card-body">
    <div class="camera-area" id="cameraArea">
      <video id="video" autoplay muted playsinline></video>
      <canvas id="canvas" style="display:none;"></canvas>
      <div class="camera-placeholder" id="cameraPlaceholder">
        <i class="fas fa-camera-retro"></i>
        <span>Mengaktifkan kamera...</span>
      </div>
    </div>

    <div class="form-group">
      <label for="namaAbsen">Nama / NIP</label>
      <input type="text" id="namaAbsen" placeholder="Masukkan nama">
    </div>
    <div class="form-group">
      <label for="roleAbsen">Status</label>
      <select id="roleAbsen">
        <option value="Siswa">Siswa</option>
        <option value="Guru">Guru</option>
      </select>
    </div>

    <div class="btn-row">
      <button id="captureBtn" class="btn btn-primary"><i class="fas fa-camera"></i> Ambil Gambar</button>
      <button id="absenBtn" class="btn btn-success" disabled><i class="fas fa-check-circle"></i> Absen Masuk</button>
    </div>

    <div id="preview" class="preview-area" style="display:none;">
      <p>Gambar tertangkap:</p>
      <img id="capturedImage" src="#" alt="Preview">
    </div>
  </div>
</div>

{{-- ── Log Hari Ini ── --}}
{{-- ── Log Hari Ini (Livewire) ── --}}
<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-history"></i> Aktivitas Hari Ini (Live)</h2>
    {{-- Filter dropdown dihapus dari sini karena sudah dipindah ke dalam komponen Livewire --}}
  </div>
  <div class="card-body" style="overflow-x:auto;">
    
    {{-- Panggil komponen Livewire-nya di sini! --}}
    <livewire:log-absensi />

  </div>
</div>
  <div class="card-body" style="overflow-x:auto;">
    <table class="simple-table" id="logTable">
      <thead><tr><th>Tanggal</th><th>Waktu</th><th>Nama</th><th>Status</th></tr></thead>
      <tbody></tbody>
    </table>
    <p id="noLogMsg" style="text-align:center;color:#777;margin-top:1rem;display:none;">Belum ada aktivitas hari ini.</p>
  </div>
</div>

@endsection

@push('scripts')
<script>
  let currentStream = null;
  let capturedImageData = null;

  const video        = document.getElementById('video');
  const canvas       = document.getElementById('canvas');
  const placeholder  = document.getElementById('cameraPlaceholder');
  const captureBtn   = document.getElementById('captureBtn');
  const absenBtn     = document.getElementById('absenBtn');
  const previewDiv   = document.getElementById('preview');
  const capturedImg  = document.getElementById('capturedImage');

  // ── Kamera ──────────────────────────────
  async function startCamera() {
    if (currentStream) return;
    try {
      currentStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
      video.srcObject = currentStream;
      video.style.display = 'block';
      placeholder.style.display = 'none';
    } catch {
      placeholder.innerHTML = '<i class="fas fa-exclamation-triangle"></i><span>Akses kamera ditolak</span>';
      showToast('Izin kamera diperlukan.', 'error');
    }
  }

  startCamera();

  captureBtn.addEventListener('click', () => {
    if (!currentStream) { showToast('Kamera belum aktif.', 'error'); return; }
    canvas.width  = video.videoWidth  || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    capturedImageData = canvas.toDataURL('image/png');
    capturedImg.src   = capturedImageData;
    previewDiv.style.display = 'block';
    absenBtn.disabled = false;
    showToast('Gambar berhasil ditangkap!', 'success');
  });

  // ── Absen ────────────────────────────────
  absenBtn.addEventListener('click', async () => {
    const nama = document.getElementById('namaAbsen').value.trim();
    if (!nama) { showToast('Nama harus diisi!', 'error'); return; }
    if (!capturedImageData) { showToast('Ambil gambar terlebih dahulu.', 'error'); return; }

    absenBtn.disabled = true;
    absenBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Memproses...';

    try {
      const res  = await apiFetch('{{ route('absensi.store') }}', {
        method: 'POST',
        body: JSON.stringify({
          nama,
          role_type: document.getElementById('roleAbsen').value,
          foto: capturedImageData,
        }),
      });
      const data = await res.json();

      if (res.ok) {
        showToast(data.message, 'success');
        document.getElementById('namaAbsen').value = '';
        capturedImageData = null;
        previewDiv.style.display = 'none';
        capturedImg.src = '#';
        loadLogs();
      } else {
        showToast(data.message || 'Terjadi kesalahan.', 'error');
      }
    } catch {
      showToast('Gagal mengirim data.', 'error');
    } finally {
      absenBtn.disabled = false;
      absenBtn.innerHTML = '<i class="fas fa-check-circle"></i> Absen Masuk';
    }
  });

  // ── Log Table ────────────────────────────
  async function loadLogs() {
    const role = document.getElementById('filterRole').value;
    const res  = await fetch(`{{ route('absensi.logs') }}?role=${role}`, { headers: { Accept: 'application/json' } });
    const logs = await res.json();
    const tbody = document.querySelector('#logTable tbody');
    const noMsg = document.getElementById('noLogMsg');

    if (logs.length === 0) {
      tbody.innerHTML = '';
      noMsg.style.display = 'block';
    } else {
      noMsg.style.display = 'none';
      tbody.innerHTML = logs.map(l =>
        `<tr><td>${l.tanggal}</td><td>${l.waktu}</td><td>${l.nama}</td><td>${l.role_type}</td></tr>`
      ).join('');
    }
  }

  document.getElementById('filterRole').addEventListener('change', loadLogs);
  loadLogs();
</script>
@endpush
