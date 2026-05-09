<?php

namespace App\Http\Controllers;

use App\Models\Guru;
use App\Models\Absensi;
use App\Models\Kategori;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GuruController extends Controller
{
    public function index(Request $request)
    {
        $today = today()->toDateString();
        $query = Guru::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('nip', 'like', "%{$search}%");
            });
        }

        $gurus = $query->orderBy('nama')->get();
        
        // Ambil kategori jabatan
        $kategoris = Kategori::orderBy('nama')->get();
        $jabatanList = $kategoris->where('tipe', 'jabatan');

        $absensiHariIni = Absensi::whereDate('tanggal', $today)
            ->where('role_type', 'Guru')
            ->pluck('status', 'nama');

        $gurus->each(function ($g) use ($absensiHariIni) {
            $g->kehadiran_hari_ini = $absensiHariIni->get($g->nama, '-');
        });

        if (request()->wantsJson()) {
            return response()->json(compact('gurus', 'jabatanList'));
        }

        return view('guru.index', compact('gurus', 'jabatanList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nip'        => 'required|string|max:30|unique:gurus,nip',
            'nama'       => 'required|string|max:100',
            'jabatan'    => 'required|string|max:100',
            'foto_wajah' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ], [
            'nip.unique' => 'NIP sudah terdaftar.',
        ]);

        $data = $request->only('nip', 'nama', 'jabatan');

        if ($request->hasFile('foto_wajah')) {
            $data['foto_wajah'] = $request->file('foto_wajah')->store('dataset/guru', 'public');
        }

        $guru = Guru::create($data);

        return response()->json(['message' => 'Data guru berhasil ditambahkan.', 'guru' => $guru], 201);
    }

    public function update(Request $request, Guru $guru)
    {
        $request->validate([
            'nip'        => "required|string|max:30|unique:gurus,nip,{$guru->id}",
            'nama'       => 'required|string|max:100',
            'jabatan'    => 'required|string|max:100',
            'foto_wajah' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = $request->only('nip', 'nama', 'jabatan');

        if ($request->hasFile('foto_wajah')) {
            if ($guru->foto_wajah) {
                Storage::disk('public')->delete($guru->foto_wajah);
            }
            $data['foto_wajah'] = $request->file('foto_wajah')->store('dataset/guru', 'public');
        }

        $guru->update($data);

        return response()->json(['message' => 'Data guru berhasil diperbarui.', 'guru' => $guru]);
    }

    public function destroy(Guru $guru)
    {
        if ($guru->foto_wajah) {
            Storage::disk('public')->delete($guru->foto_wajah);
        }
        $guru->delete();
        return response()->json(['message' => 'Data guru berhasil dihapus.']);
    }
}