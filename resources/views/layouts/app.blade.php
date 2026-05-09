<!DOCTYPE html>
<html lang="id">
@livewireStyles
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>AbsensiKu – @yield('title', 'Dashboard')</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
  <style>
    *{margin:0;padding:0;box-sizing:border-box}
    body{font-family:'Inter','Segoe UI',system-ui,-apple-system,sans-serif;background:#F5F5F5;color:#333;line-height:1.6}

    /* ── Wrapper ──────────────────────────── */
    .wrapper{display:flex;min-height:100vh}

    /* ── Sidebar ──────────────────────────── */
    .sidebar{width:260px;background:#fff;border-right:1px solid #E0E0E0;display:flex;flex-direction:column;transition:transform .3s;box-shadow:2px 0 8px rgba(0,0,0,.03)}
    .sidebar-header{padding:1.5rem 1rem;border-bottom:1px solid #F0F0F0}
    .logo{font-size:1.4rem;font-weight:600;color:#4A90E2}
    .sidebar-nav{flex:1;padding:1rem 0}
    .nav-item{display:flex;align-items:center;gap:.75rem;padding:.75rem 1.5rem;color:#555;text-decoration:none;font-weight:500;border-left:3px solid transparent;transition:background .2s,color .2s}
    .nav-item:hover{background:#F9F9F9;color:#333}
    .nav-item.active{background:#F0F7FF;color:#4A90E2;border-left-color:#4A90E2}
    .sidebar-footer{border-top:1px solid #F0F0F0;padding:.5rem 0}

    /* ── Main ─────────────────────────────── */
    .main{flex:1;display:flex;flex-direction:column;overflow-x:hidden}
    .topbar{background:#fff;padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem;border-bottom:1px solid #E0E0E0;box-shadow:0 1px 3px rgba(0,0,0,.03)}
    .toggle-btn{background:none;border:none;font-size:1.5rem;cursor:pointer;color:#666;display:none}
    .page-title{font-size:1.3rem;font-weight:600;flex:1}
    .user-info{display:flex;align-items:center;gap:.5rem;font-weight:500}
    .avatar{width:36px;height:36px;border-radius:50%;background:#E0E0E0;display:flex;align-items:center;justify-content:center;font-weight:600;color:#555}
    .logout-btn{background:none;border:none;color:#E74C3C;cursor:pointer;font-size:.9rem;margin-left:1rem}

    /* ── Content & Cards ─────────────────── */
    .content{padding:1.5rem;display:grid;gap:1.5rem}
    .card{background:#fff;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.05);overflow:hidden}
    .card-header{padding:1rem 1.5rem;border-bottom:1px solid #F0F0F0;display:flex;justify-content:space-between;align-items:center}
    .card-header h2{font-size:1.2rem;font-weight:600;display:flex;align-items:center;gap:.5rem}
    .card-body{padding:1.5rem}

    /* ── Buttons ─────────────────────────── */
    .btn{padding:.5rem 1rem;border:none;border-radius:6px;font-weight:500;cursor:pointer;display:inline-flex;align-items:center;gap:.4rem;font-size:.9rem;transition:background .2s;text-decoration:none}
    .btn-primary{background:#4A90E2;color:#fff}.btn-primary:hover{background:#357ABD}
    .btn-success{background:#27AE60;color:#fff}.btn-success:hover{background:#1E8449}
    .btn-danger{background:#E74C3C;color:#fff}.btn-danger:hover{background:#C0392B}
    .btn-sm{padding:.3rem .7rem;font-size:.8rem}
    .btn:disabled{opacity:.6;pointer-events:none}

    /* ── Forms ───────────────────────────── */
    .form-group{margin-bottom:1rem;text-align:left}
    .form-group label{font-weight:500;margin-bottom:.3rem;display:block;color:#555}
    .form-group input,.form-group select{width:100%;padding:.7rem;border:1px solid #DDD;border-radius:8px;font-size:1rem}

    /* ── Tables ──────────────────────────── */
    .simple-table{width:100%;border-collapse:collapse;margin-top:.5rem}
    .simple-table th,.simple-table td{padding:.6rem;text-align:left;border-bottom:1px solid #F0F0F0;font-size:.9rem}
    .simple-table th{color:#777;font-weight:500}

    /* ── Filter bar ──────────────────────── */
    .filter-bar{display:flex;gap:1rem;align-items:center;flex-wrap:wrap}
    .filter-bar select{padding:.4rem .75rem;border-radius:6px;border:1px solid #DDD;font-size:.9rem}

    /* ── Modal ───────────────────────────── */
    .modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.4);display:none;align-items:center;justify-content:center;z-index:1000}
    .modal-overlay.show{display:flex}
    .modal{background:#fff;border-radius:12px;padding:2rem;width:90%;max-width:500px;max-height:80vh;overflow-y:auto;box-shadow:0 10px 30px rgba(0,0,0,.2)}
    .modal h3{font-size:1.1rem;font-weight:600;margin-bottom:1.2rem}
    .modal-buttons{display:flex;gap:.5rem;justify-content:flex-end;margin-top:1rem}

    /* ── Toast ───────────────────────────── */
    .toast-container{position:fixed;top:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:.5rem}
    .toast{background:#333;color:#fff;padding:.75rem 1.25rem;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,.15);display:flex;align-items:center;gap:.5rem;animation:toastIn .3s,toastOut .3s 3s forwards}
    .toast.success{background:#27AE60}.toast.error{background:#E74C3C}
    @keyframes toastIn{from{opacity:0;transform:translateX(100%)}to{opacity:1;transform:translateX(0)}}
    @keyframes toastOut{from{opacity:1}to{opacity:0;transform:translateX(100%)}}

    /* ── Responsive ──────────────────────── */
    @media(max-width:768px){
      .sidebar{position:fixed;top:0;left:0;height:100vh;transform:translateX(-100%);z-index:1000}
      .sidebar.show{transform:translateX(0)}
      .toggle-btn{display:block}
    }
  </style>
  @stack('styles')
</head>
@livewireScripts
<body>
<div class="wrapper">
  {{-- ── Sidebar ── --}}
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header"><h2 class="logo"><i class="fas fa-calendar-check"></i> Face Attendant</h2></div>
    <nav class="sidebar-nav">
      <a href="{{ route('dashboard') }}" class="nav-item @yield('nav_dashboard')"><i class="fas fa-home"></i><span>Dashboard</span></a>
     
      @if(auth()->user()->isAdmin())
      <a href="{{ route('absensi.index') }}" class="nav-item @yield('nav_absensi')"><i class="fas fa-camera"></i><span>Kamera Absensi</span></a>
      <a href="{{ route('siswa.index') }}"   class="nav-item @yield('nav_siswa')"><i class="fas fa-users"></i><span>Data Siswa</span></a>
      <a href="{{ route('guru.index') }}"    class="nav-item @yield('nav_guru')"><i class="fas fa-chalkboard-teacher"></i><span>Data Guru</span></a>
      <a href="{{ route('devices.index') }}" class="nav-item @yield('nav_device')"><i class="fas fa-microchip"></i><span>Perangkat IoT</span></a>
      <a href="{{ route('laporan.index') }}" class="nav-item @yield('nav_laporan')"><i class="fas fa-file-alt"></i><span>Laporan</span></a>
      @endif
    </nav>
    <div class="sidebar-footer">
      <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="nav-item" style="width:100%;background:none;border:none;cursor:pointer;">
          <i class="fas fa-sign-out-alt"></i><span>Keluar</span>
        </button>
      </form>
    </div>
  </aside>

  {{-- ── Main ── --}}
  <div class="main">
    <header class="topbar">
      <button id="sidebarToggle" class="toggle-btn"><i class="fas fa-bars"></i></button>
      <h1 class="page-title">@yield('page_title', 'AbsensiKu')</h1>
      <div class="user-info">
        <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
        <span>{{ auth()->user()->name }}</span>
        <form method="POST" action="{{ route('logout') }}" style="display:inline;">
          @csrf
          <button type="submit" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Keluar</button>
        </form>
      </div>
    </header>

    <section class="content">
      @yield('content')
    </section>
  </div>
</div>

{{-- ── Global Modal ── --}}
<div class="modal-overlay" id="globalModal">
  <div class="modal" id="globalModalContent"></div>
</div>

{{-- ── Toast Container ── --}}
<div id="toastContainer" class="toast-container"></div>

<script>
  // Sidebar toggle (mobile)
  const sidebar = document.getElementById('sidebar');
  document.getElementById('sidebarToggle').addEventListener('click', () => sidebar.classList.toggle('show'));
  document.addEventListener('click', (e) => {
    if (window.innerWidth <= 768 && !sidebar.contains(e.target) && e.target.id !== 'sidebarToggle')
      sidebar.classList.remove('show');
  });

  // Toast helper
  function showToast(msg, type = 'success') {
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${msg}`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 3500);
  }

  // Global modal helpers
  const globalModal   = document.getElementById('globalModal');
  const globalContent = document.getElementById('globalModalContent');
  function openModal(html) { globalContent.innerHTML = html; globalModal.classList.add('show'); }
  function closeModal()    { globalModal.classList.remove('show'); }
  globalModal.addEventListener('click', (e) => { if (e.target === globalModal) closeModal(); });

  // CSRF helper untuk fetch
  const CSRF = document.querySelector('meta[name="csrf-token"]').content;
  function apiFetch(url, opts = {}) {
    return fetch(url, {
      headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json', 'Content-Type': 'application/json', ...(opts.headers || {}) },
      ...opts,
    });
  }

  @if(session('success'))
    showToast("{{ session('success') }}", 'success');
  @endif
  @if(session('error'))
    showToast("{{ session('error') }}", 'error');
  @endif
</script>
@stack('scripts')
</body>
</html>
