@extends('layouts.app')

@section('title', 'Data Guru')
@section('page_title', 'Data Guru')
@section('nav_guru', 'active')

@section('content')
<div class="card">
  <div class="card-header">
    <h2><i class="fas fa-chalkboard-teacher"></i> Data Guru</h2>
    <div style="display: flex; gap: 0.5rem; align-items: center; flex-wrap: wrap;">
        
        <form method="GET" action="{{ route('guru.index') }}" style="margin: 0; display:flex; gap:0.5rem;">
            <input type="text" name="search" placeholder="Cari Nama / NIP..." value="{{ request('search') }}" style="padding: 0.35rem 0.5rem; border-radius: 4px; border: 1px solid #ddd; outline: none; width: 200px;">
            <button type="submit" style="display: none;"></button>
        </form>

        <button class="btn btn-success btn-sm" onclick="openKategoriModal()"><i class="fas fa-tags"></i> Kelola Jabatan</button>
        <button class="btn btn-primary btn-sm" onclick="openGuruModal()">
            <i class="fas fa-plus"></i> Tambah Guru
        </button>
    </div>
  </div>

  <div class="card-body" style="overflow-x:auto;">
    <table class="simple-table" id="tabelGuru">
      <thead>
        <tr>
            <th>NIP</th>
            <th>Nama</th>
            <th>Jabatan</th>
            <th>Kehadiran Hari Ini</th>
            <th>Aksi</th>
        </tr>
      </thead>
      <tbody>
        @forelse($gurus as $g)
        <tr id="row-guru-{{ $g->id }}">
          <td>{{ $g->nip }}</td>
          <td>
            <div style="display: flex; align-items: center; gap: 10px;">
                @if($g->foto_wajah)
                    <img src="{{ asset('storage/' . $g->foto_wajah) }}" style="width:30px; height:30px; border-radius:50%; object-fit:cover; border: 1px solid #ddd;">
                @else
                    <div style="width:30px; height:30px; border-radius:50%; background:#f0f0f0; color:#aaa; display:flex; align-items:center; justify-content:center; font-size:12px;">
                        <i class="fas fa-user-tie"></i>
                    </div>
                @endif
                <strong>{{ $g->nama }}</strong>
            </div>
          </td>
          <td>{{ $g->jabatan }}</td>
          <td>
            <select class="kehadiran-select-guru" data-nama="{{ $g->nama }}" style="padding:.25rem .5rem;border-radius:4px;border:1px solid #DDD; outline:none;">
              @foreach(['Hadir','Izin','Sakit'] as $st)
                <option value="{{ $st }}" {{ $g->kehadiran_hari_ini === $st ? 'selected' : '' }}>{{ $st }}</option>
              @endforeach
            </select>
          </td>
          <td>
            <button class="btn btn-primary btn-sm" onclick='openGuruModal(@json($g))' title="Edit">
              <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-danger btn-sm" onclick="hapusGuru({{ $g->id }}, '{{ addslashes($g->nama) }}')" title="Hapus">
              <i class="fas fa-trash"></i>
            </button>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:#aaa;padding:2rem;">Belum ada data guru.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
@endsection

@push('scripts')
<script>
// ==========================================
// 1. LOGIKA CRUD GURU
// ==========================================
function openGuruModal(data = null) {
  const isEdit = data !== null;
  const opsiJabatan = `@foreach($jabatanList as $j) <option value="{{ $j->nama }}">{{ $j->nama }}</option> @endforeach`;

  openModal(`
    <h3>${isEdit ? 'Edit' : 'Tambah'} Guru</h3>
    <form id="formGuru" onsubmit="event.preventDefault(); simpanGuru(${isEdit ? data.id : 'null'});">
        <div class="form-group">
            <label>NIP</label>
            <input type="text" id="mNip" value="${isEdit ? data.nip : ''}" required>
        </div>
        
        <div class="form-group">
            <label>Nama Lengkap</label>
            <input type="text" id="mNamaGuru" value="${isEdit ? data.nama : ''}" required>
        </div>
        
        <div class="form-group">
            <label>Jabatan</label>
            <select id="mJabatan" required>
                <option value="" disabled ${!isEdit ? 'selected' : ''}>-- Pilih Jabatan --</option>
                ${opsiJabatan}
            </select>
        </div>
        
        <div class="form-group">
            <label>Foto Wajah (Untuk Dataset AI)</label>
            <input type="file" id="mFoto" accept="image/png, image/jpeg">
            ${isEdit && data.foto_wajah ? `<small style="color:#27AE60; display:block; margin-top:5px;"><i class="fas fa-check-circle"></i> Foto tersimpan. Kosongkan jika tidak ingin mengganti.</small>` : ''}
        </div>

        <div class="modal-buttons">
          <button type="button" class="btn btn-danger btn-sm" onclick="closeModal()">Batal</button>
          <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Simpan</button>
        </div>
    </form>`);

    if (isEdit) {
        let selJabatan = document.getElementById('mJabatan');
        if(![...selJabatan.options].some(o => o.value === data.jabatan)) {
            selJabatan.add(new Option(data.jabatan, data.jabatan));
        }
        selJabatan.value = data.jabatan;
    }
}

async function simpanGuru(id) {
  const formData = new FormData();
  formData.append('nip', document.getElementById('mNip').value.trim());
  formData.append('nama', document.getElementById('mNamaGuru').value.trim());
  formData.append('jabatan', document.getElementById('mJabatan').value.trim());

  const fotoFile = document.getElementById('mFoto').files[0];
  if (fotoFile) formData.append('foto_wajah', fotoFile);

  if (id) formData.append('_method', 'PUT');

  const url = id ? `/guru/${id}` : '{{ route('guru.store') }}';
  
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
  } catch(e) {
      showToast('Gagal terhubung ke server.', 'error');
  }
}

async function hapusGuru(id, nama) {
  if (!confirm(`Hapus permanen data guru "${nama}" beserta fotonya?`)) return;
  
  const res = await apiFetch(`/guru/${id}`, { method: 'DELETE' });
  const data = await res.json();
  if (res.ok) { 
      showToast(data.message, 'success');
      document.getElementById(`row-guru-${id}`).remove(); 
  } else {
      showToast(data.message || 'Gagal menghapus.', 'error');
  }
}

document.querySelectorAll('.kehadiran-select-guru').forEach(sel => {
  sel.addEventListener('change', async (e) => {
    const nama   = e.target.dataset.nama;
    const status = e.target.value;
    const res    = await apiFetch('{{ route('absensi.store') }}', {
      method: 'POST',
      body: JSON.stringify({ nama, role_type: 'Guru' }),
    });
    if (res.ok) {
      const absensi = (await res.json()).absensi;
      await apiFetch(`/absensi/${absensi.id}/status`, { method: 'PATCH', body: JSON.stringify({ status }) });
      showToast(`Status kehadiran ${nama} diubah jadi ${status}.`, 'success');
    }
  });
});

// ==========================================
// 2. LOGIKA KELOLA KATEGORI (JABATAN)
// ==========================================
function openKategoriModal() {
    const listJ = `@foreach($jabatanList as $j) 
        <li style="display:flex; justify-content:space-between; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f0f0f0;">
            <span>{{ $j->nama }}</span> 
            <button type="button" class="btn btn-danger btn-sm" onclick="hapusKategori({{ $j->id }}, this)" style="padding:2px 6px;"><i class="fas fa-times"></i></button>
        </li> 
    @endforeach`;

    openModal(`
    <h3><i class="fas fa-tags"></i> Kelola Jabatan</h3>
    <div style="border:1px solid #ddd; padding:1rem; border-radius:8px; background:#f9f9f9;">
        <h4 style="margin-bottom:10px; font-size:0.95rem; color:#555;">Daftar Jabatan</h4>
        <ul style="list-style:none; padding:0; margin-bottom:15px; height:200px; overflow-y:auto; background:#fff; border:1px solid #eee; padding:5px; border-radius:4px;">
            ${listJ || '<li style="color:#aaa; text-align:center; padding:1rem 0;">Belum ada jabatan</li>'}
        </ul>
        <div style="display:flex; gap:5px;">
            <input id="inputJabatan" placeholder="Tambah jabatan baru..." style="flex:1; padding:5px 8px; border:1px solid #ccc; border-radius:4px;">
            <button type="button" class="btn btn-success btn-sm" onclick="tambahKategori('jabatan')"><i class="fas fa-plus"></i></button>
        </div>
    </div>
    <div class="modal-buttons" style="margin-top:1.5rem;">
        <button type="button" class="btn btn-primary" onclick="closeModal(); location.reload();" style="width:100%;">Tutup & Segarkan Halaman</button>
    </div>
    `);
}

async function tambahKategori(tipe) {
    const inputEl = document.getElementById('inputJabatan');
    const nama = inputEl.value.trim();
    if(!nama) return;

    // Ubah tombol jadi indikator loading sementara biar user tahu sistem sedang bekerja
    const btn = inputEl.nextElementSibling;
    const oldIcon = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    btn.disabled = true;

    try {
        const res = await fetch('{{ route('kategori.store') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({ tipe, nama })
        });

        const data = await res.json();

        if(res.ok) {
            showToast('Jabatan ditambahkan!', 'success');
            
            const ul = inputEl.parentElement.previousElementSibling;
            // Hapus teks 'Belum ada jabatan' jika ini input pertama
            if (ul.firstElementChild && ul.firstElementChild.style.color) ul.innerHTML = '';
            
            ul.insertAdjacentHTML('beforeend', `
                <li style="display:flex; justify-content:space-between; margin-bottom:5px; padding-bottom:5px; border-bottom:1px solid #f0f0f0;">
                    <span>${nama}</span> 
                    <button type="button" class="btn btn-danger btn-sm" onclick="hapusKategori(${data.kategori.id}, this)" style="padding:2px 6px;"><i class="fas fa-times"></i></button>
                </li>
            `);
            inputEl.value = ''; 
        } else {
            // Jika ditolak server, tampilkan alasannya
            showToast(data.message || 'Gagal menambahkan jabatan.', 'error');
            console.error('Error detail:', data);
        }
    } catch (e) {
        showToast('Kesalahan jaringan saat menghubungi server.', 'error');
        console.error(e);
    } finally {
        // Kembalikan kondisi tombol
        btn.innerHTML = oldIcon;
        btn.disabled = false;
    }
}

async function hapusKategori(id, btnElement) {
    if(!confirm('Yakin ingin menghapus jabatan ini? Data guru terkait tidak akan terhapus.')) return;
    
    try {
        const res = await fetch(`/kategori/${id}`, { 
            method: 'DELETE',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await res.json();
        
        if(res.ok) {
            showToast('Jabatan dihapus!', 'success');
            btnElement.closest('li').remove();
        } else {
            showToast(data.message || 'Gagal menghapus jabatan.', 'error');
        }
    } catch (e) {
        showToast('Kesalahan jaringan.', 'error');
    }
}
</script>
@endpush