@extends('layouts.app')

@section('title', 'Data Siswa')
@section('page_title', 'Data Siswa')
@section('nav_siswa', 'active')

@section('content')
<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-users"></i> Data Siswa</h2>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        
        <form method="GET" action="{{ route('siswa.index') }}" style="margin: 0; display:flex; gap:0.5rem; align-items:center;">
            <input type="text" name="search" placeholder="Cari Nama / NIS..." value="{{ request('search') }}" style="padding: 0.35rem 0.5rem; border-radius: 4px; border: 1px solid #ddd; outline: none; width: 180px;">
            
            <select name="kelas" onchange="this.form.submit()" style="padding: 0.35rem 0.5rem; border-radius: 4px; border: 1px solid #ddd; outline: none;">
                <option value="Semua">Semua Kelas</option>
                @foreach($kelasList as $k)
                    <option value="{{ $k->nama }}" {{ request('kelas') == $k->nama ? 'selected' : '' }}>{{ $k->nama }}</option>
                @endforeach
            </select>
            <button type="submit" style="display: none;"></button>
        </form>

        <button class="btn btn-success btn-sm" onclick="openKategoriModal()"><i class="fas fa-tags"></i> Kelola Kategori</button>
        <button class="btn btn-primary btn-sm" onclick="openSiswaModal()"><i class="fas fa-plus"></i> Tambah Siswa</button>
    </div>
  </div>

  <div class="card-body" style="overflow-x:auto;">
    <table class="simple-table" id="tabelSiswa">
      <thead>
        <tr>
          <th>NIS</th>
          <th>Nama</th>
          <th>Kelas</th>
          <th>Jurusan</th>
          <th>Kehadiran Hari Ini</th>
          <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($siswas as $s)
        <tr id="row-siswa-{{ $s->id }}">
          <td>{{ $s->nis }}</td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
                @if($s->foto_wajah)
                    <img src="{{ asset('storage/' . $s->foto_wajah) }}" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border: 1px solid #ddd;">
                @else
                    <div style="width:30px; height:30px; border-radius:50%; background:#f0f0f0; color:#aaa; display:flex; align-items:center; justify-content:center; font-size:12px;">
                        <i class="fas fa-user"></i>
                    </div>
                @endif
                <strong>{{ $s->nama }}</strong>
            </div>
          </td>
          <td>{{ $s->kelas }}</td>
          <td>{{ $s->jurusan }}</td>
          <td>
            <select class="kehadiran-select-siswa" data-nama="{{ $s->nama }}" style="padding:.25rem .5rem; border-radius:4px; border:1px solid #DDD; outline:none;">
              @foreach(['Hadir','Izin','Sakit'] as $st)
                <option value="{{ $st }}" {{ $s->kehadiran_hari_ini === $st ? 'selected' : '' }}>{{ $st }}</option>
              @endforeach
            </select>
          </td>
          <td>
            <button class="btn btn-primary btn-sm" onclick='openSiswaModal(@json($s))' title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm" onclick="hapusSiswa({{ $s->id }}, '{{ addslashes($s->nama) }}')" title="Hapus">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
        @empty
        <tr><td colspan="6" style="text-align:center;color:#aaa;padding:2rem;">Belum ada data siswa. Silakan tambah data baru.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
// ==========================================
// 1. LOGIKA CRUD SISWA
// ==========================================
function openSiswaModal(data = null) {
  const isEdit = data !== null;
  
  const opsiKelas = `@foreach($kelasList as $k) <option value="{{ $k->nama }}">{{ $k->nama }}</option> @endforeach`;
  const opsiJurusan = `@foreach($jurusanList as $j) <option value="{{ $j->nama }}">{{ $j->nama }}</option> @endforeach`;

  openModal(`
    <h3>${isEdit ? 'Edit' : 'Tambah'} Siswa</h3>
    <form id="formSiswa" onsubmit="event.preventDefault(); simpanSiswa(${isEdit ? data.id : 'null'});">
        <div class="form-group">
            <label>NIS</label>
            <input type="text" id="mNis" value="${isEdit ? data.nis : ''}" required>
        </div>
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" id="mNamaSiswa" value="${isEdit ? data.nama : ''}" required>
        </div>
        
        <div style="display:flex; gap:1rem;">
            <div class="form-group" style="flex:1;">
                <label>Kelas</label>
                <select id="mKelas" required>
                    <option value="" disabled ${!isEdit ? 'selected' : ''}>-- Pilih Kelas --</option>
                    ${opsiKelas}
                </select>
            </div>
            
            <div class="form-group" style="flex:1;">
                <label>Jurusan</label>
                <select id="mJurusan" required>
                    <option value="" disabled ${!isEdit ? 'selected' : ''}>-- Pilih Jurusan --</option>
                    ${opsiJurusan}
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Foto Wajah (Untuk Dataset AI)</label>
            
            ${isEdit && data.foto_wajah ? `
                <div style="margin-bottom: 10px; padding: 10px; border: 1px dashed #ccc; border-radius: 6px; background: #f9f9f9; display: inline-block;">
                    <img src="/storage/${data.foto_wajah}" style="width: 80px; height: 80px; border-radius: 8px; object-fit: cover; border: 1px solid #ddd; margin-bottom: 5px; display: block;">
                    <small style="color:#27AE60; font-weight: bold;"><i class="fas fa-check-circle"></i> Foto Tersimpan</small>
                </div>
            ` : ''}
            
            <input type="file" id="mFoto" accept="image/png, image/jpeg" style="margin-top: 5px;">
            <small style="color: #888; display: block; margin-top: 5px;">
                *Biarkan 'No file chosen' jika tidak ingin mengganti foto saat ini.
            </small>
        </div>

        <div class="modal-buttons">
          <button type="button" class="btn btn-danger btn-sm" onclick="closeModal()">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>`);

    // Set Value Dropdown jika mode edit, meskipun datanya terhapus di kategori
    if (isEdit) {
        let selKelas = document.getElementById('mKelas');
        if(![...selKelas.options].some(o => o.value === data.kelas)) selKelas.add(new Option(data.kelas, data.kelas));
        selKelas.value = data.kelas;

        let selJurusan = document.getElementById('mJurusan');
        if(![...selJurusan.options].some(o => o.value === data.jurusan)) selJurusan.add(new Option(data.jurusan, data.jurusan));
        selJurusan.value = data.jurusan;
    }
}

async function simpanSiswa(id) {
  const formData = new FormData();
  formData.append('nis', document.getElementById('mNis').value.trim());
  formData.append('nama', document.getElementById('mNamaSiswa').value.trim());
  formData.append('kelas', document.getElementById('mKelas').value.trim());
  formData.append('jurusan', document.getElementById('mJurusan').value.trim());

  const fotoFile = document.getElementById('mFoto').files[0];
  if (fotoFile) {
      formData.append('foto_wajah', fotoFile);
  }

  // Inject PUT method untuk Laravel
  if (id) formData.append('_method', 'PUT');

  const url = id ? `/siswa/${id}` : '{{ route('siswa.store') }}';
  
  try {
      const res = await fetch(url, {
          method: 'POST', 
          headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
              'Accept': 'application/json'
          },
          body: formData
      });

      const resultData = await res.json();

      if (res.ok) {
        showToast(resultData.message, 'success');
        closeModal();
        setTimeout(() => location.reload(), 600);
      } else {
        const msg = resultData.errors ? Object.values(resultData.errors).flat().join('\n') : (resultData.message || 'Gagal menyimpan.');
        showToast(msg, 'error');
      }
  } catch (err) {
      showToast('Gagal terhubung ke server.', 'error');
  }
}

async function hapusSiswa(id, nama) {
  if (!confirm(`Hapus permanen data siswa "${nama}" beserta foto wajahnya?`)) return;
  
  const res = await apiFetch(`/siswa/${id}`, { method: 'DELETE' });
  const data = await res.json();
  
  if (res.ok) { 
      showToast(data.message, 'success');
      document.getElementById(`row-siswa-${id}`).remove(); 
  } else {
      showToast(data.message || 'Gagal menghapus.', 'error');
  }
}

// Fitur ubah absensi dadakan via dropdown tabel
document.querySelectorAll('.kehadiran-select-siswa').forEach(sel => {
  sel.addEventListener('change', async (e) => {
    const nama   = e.target.dataset.nama;
    const status = e.target.value;
    const res    = await apiFetch('{{ route('absensi.store') }}', {
      method: 'POST',
      body: JSON.stringify({ nama, role_type: 'Siswa' }),
    });
    if (res.ok) {
      const absensi = (await res.json()).absensi;
      await apiFetch(`/absensi/${absensi.id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) });
      showToast(`Status kehadiran ${nama} diubah jadi ${status}.`, 'success');
    }
  });
});


// ==========================================
// 2. LOGIKA KELOLA KATEGORI
// ==========================================
function openKategoriModal() {
    const listK = `@foreach($kelasList as $k) 
        <li style="display:flex; justify-content:space-between; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f0f0f0;">
            <span>{{ $k->nama }}</span> 
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusKategori({{ $k->id }}, this)" style="padding:2px 6px;"><i class="fas fa-times"></i></button>
        </li> 
    @endforeach`;
    
    const listJ = `@foreach($jurusanList as $j) 
        <li style="display:flex; justify-content:space-between; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f0f0f0;">
            <span>{{ $j->nama }}</span> 
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusKategori({{ $j->id }}, this)" style="padding:2px 6px;"><i class="fas fa-times"></i></button>
        </li> 
    @endforeach`;

    openModal(`
    <h3><i class="fas fa-tags"></i> Kelola Kategori</h3>
    <div style="display:flex; gap:1rem;">
        <div style="flex:1; border:1px solid #ddd; padding:1rem; border-radius:8px; background:#f9f9f9;">
            <h4 style="margin-bottom:10px; font-size:0.95rem; color:#555;">Daftar Kelas</h4>
            <ul style="list-style:none; padding:0; margin-bottom:15px; height:150px; overflow-y:auto; background:#fff; border:1px solid #eee; padding:5px; border-radius:4px;">
                ${listK || '<li style="color:#aaa; text-align:center; padding:1rem 0;">Belum ada kelas</li>'}
            </ul>
            <div style="display:flex; gap:5px;">
                <input id="inputKelas" placeholder="Tambah kelas baru..." style="flex:1; padding:5px 8px; border:1px solid #ccc; border-radius:4px;">
                <button type="button" class="btn btn-success btn-sm" onclick="tambahKategori('kelas')"><i class="fas fa-plus"></i></button>
            </div>
        </div>

        <div style="flex:1; border:1px solid #ddd; padding:1rem; border-radius:8px; background:#f9f9f9;">
            <h4 style="margin-bottom:10px; font-size:0.95rem; color:#555;">Daftar Jurusan</h4>
            <ul style="list-style:none; padding:0; margin-bottom:15px; height:150px; overflow-y:auto; background:#fff; border:1px solid #eee; padding:5px; border-radius:4px;">
                ${listJ || '<li style="color:#aaa; text-align:center; padding:1rem 0;">Belum ada jurusan</li>'}
            </ul>
            <div style="display:flex; gap:5px;">
                <input id="inputJurusan" placeholder="Tambah jurusan baru..." style="flex:1; padding:5px 8px; border:1px solid #ccc; border-radius:4px;">
                <button type="button" class="btn btn-success btn-sm" onclick="tambahKategori('jurusan')"><i class="fas fa-plus"></i></button>
            </div>
        </div>
    </div>
    <div class="modal-buttons" style="margin-top:1.5rem;">
        <button type="button" class="btn btn-primary" onclick="closeModal(); location.reload();" style="width:100%;">Tutup & Segarkan Halaman</button>
    </div>
    `);
}

async function tambahKategori(tipe) {
    const inputEl = document.getElementById(tipe === 'kelas' ? 'inputKelas' : 'inputJurusan');
    const nama = inputEl.value.trim();
    if(!nama) return;

    const res = await apiFetch('{{ route('kategori.store') }}', {
        method: 'POST',
        body: JSON.stringify({ tipe, nama })
    });

    if(res.ok) {
        const data = await res.json();
        showToast('Kategori ditambahkan!', 'success');
        
        const ul = inputEl.parentElement.previousElementSibling;
        if (ul.firstElementChild && ul.firstElementChild.style.color) ul.innerHTML = '';
        
        ul.insertAdjacentHTML('beforeend', `
            <li style="display:flex; justify-content:space-between; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f0f0f0;">
                <span>${nama}</span> 
                <button type="button" class="btn btn-danger btn-sm" onclick="hapusKategori(${data.kategori.id}, this)" style="padding:2px 6px;"><i class="fas fa-times"></i></button>
            </li>
        `);
        inputEl.value = ''; 
    }
}

async function hapusKategori(id, btnElement) {
    if(!confirm('Yakin ingin menghapus kategori ini? Data siswa terkait tidak akan terhapus.')) return;
    
    const res = await apiFetch(`/kategori/${id}`, { method: 'DELETE' });
    if(res.ok) {
        showToast('Kategori dihapus!', 'success');
        btnElement.closest('li').remove();
    }
}
</script>
@endpush