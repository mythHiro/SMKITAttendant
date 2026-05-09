<?php

namespace App\Http\Controllers;

use App\Models\Siswa;
use App\Models\Absensi;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiswaController extends Controller
{
    public function index(Request $request)
    {
        $today = today()->toDateString();
        $query = Siswa::query();

        // 1. Filter Kelas
        if ($request->filled('kelas') && $request->kelas !== 'Semua') {
            $query->where('kelas', $request->kelas);
        }

        // 2. Filter Pencarian (Nama / NIS)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%");
            });
        }

        $siswas = $query->orderBy('nama')->get();
        
        $kategoris = Kategori::orderBy('nama')->get();
        $kelasList = $kategoris->where('tipe', 'kelas');
        $jurusanList = $kategoris->where('tipe', 'jurusan');

        $absensiHariIni = Absensi::whereDate('tanggal', $today)->where('role_type', 'Siswa')->pluck('status', 'nama');
        $siswas->each(function ($s) use ($absensiHariIni) {
            $s->kehadiran_hari_ini = $absensiHariIni->get($s->nama, '-');
        });

        if ($request->wantsJson()) {
            return response()->json(compact('siswas', 'kelasList', 'jurusanList'));
        }

        return view('siswa.index', compact('siswas', 'kelasList', 'jurusanList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nis'        => 'required|string|max:20|unique:siswas,nis',
            'nama'       => 'required|string|max:100',
            'kelas'      => 'required|string|max:20',
            'jurusan'    => 'required|string|max:50',
            // Validasi foto: harus berupa gambar, format jpeg/png, maksimal 2MB
            'foto_wajah' => 'nullable|image|mimes:jpeg,png,jpg|max:2048', 
        ], [
            'nis.unique' => 'NIS sudah terdaftar.',
        ]);

        $data = $request->only('nis', 'nama', 'kelas', 'jurusan');

        // Simpan foto master ke storage public jika ada file yang diupload
        if ($request->hasFile('foto_wajah')) {
            $data['foto_wajah'] = $request->file('foto_wajah')->store('dataset/siswa', 'public');
        }

        $siswa = Siswa::create($data);

        return response()->json(['message' => 'Data siswa berhasil ditambahkan.', 'siswa' => $siswa], 201);
    }

    public function update(Request $request, Siswa $siswa)
    {
        $request->validate([
            'nis'        => "required|string|max:20|unique:siswas,nis,{$siswa->id}",
            'nama'       => 'required|string|max:100',
            'kelas'      => 'required|string|max:20',
            'jurusan'    => 'required|string|max:50',
            'foto_wajah' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only('nis', 'nama', 'kelas', 'jurusan');

        // Jika user mengupload foto baru saat mengedit
        if ($request->hasFile('foto_wajah')) {
            // Hapus foto lama agar storage tidak penuh dengan file zombie
            if ($siswa->foto_wajah) {
                Storage::disk('public')->delete($siswa->foto_wajah);
            }
            // Simpan foto yang baru
            $data['foto_wajah'] = $request->file('foto_wajah')->store('dataset/siswa', 'public');
        }

        $siswa->update($data);

        return response()->json(['message' => 'Data siswa berhasil diperbarui.', 'siswa' => $siswa]);
    }

    public function destroy(Siswa $siswa)
    {
        // Bersihkan foto di storage terlebih dahulu sebelum data dihapus dari database
        if ($siswa->foto_wajah) {
            Storage::disk('public')->delete($siswa->foto_wajah);
        }
        
        $siswa->delete();
        
        return response()->json(['message' => 'Data siswa berhasil dihapus.']);
    }

    // ── Fungsi Pengelolaan Kategori ──

    public function storeKategori(Request $request)
    {
        $request->validate([
            // Tambahkan 'jabatan' di sini
            'tipe' => 'required|in:kelas,jurusan,jabatan', 
            'nama' => 'required|string|max:50'
        ]);

        $kategori = Kategori::firstOrCreate($request->only('tipe', 'nama'));
        
        return response()->json([
            'message' => 'Kategori berhasil ditambahkan.',
            'kategori' => $kategori
        ]);
    }

    public function destroyKategori(Kategori $kategori)
    {
        $kategori->delete();
        
        return response()->json(['message' => 'Kategori berhasil dihapus.']);
    }
}