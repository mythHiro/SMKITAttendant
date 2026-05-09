<?php

namespace App\Http\Controllers;

use App\Models\Absensi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AbsensiController extends Controller
{
    public function index()
    {
        return view('absensi.index');
    }

    /** POST /absensi — simpan absensi baru */
    public function store(Request $request)
    {
        $request->validate([
            'nama'      => 'required|string|max:100',
            'role_type' => 'required|in:Siswa,Guru',
            'foto'      => 'nullable|string', // base64
        ], [
            'nama.required'      => 'Nama harus diisi.',
            'role_type.required' => 'Status harus dipilih.',
        ]);

        $today  = today()->toDateString();
        $waktu  = now()->format('H:i');

        // Simpan / update record hari ini
        $absensi = Absensi::firstOrNew([
            'tanggal'   => $today,
            'nama'      => $request->nama,
            'role_type' => $request->role_type,
        ]);

        $absensi->waktu  = $waktu;
        $absensi->status = 'Hadir';

        // Simpan foto jika ada
        // Simpan foto jika ada
        if ($request->filled('foto')) {
            // 1. Ekstrak header gambar dan payload base64
            if (preg_match('/^data:image\/(\w+);base64,/', $request->foto, $matches)) {
                $extension = strtolower($matches[1]);
                
                // Pastikan ekstensi awal yang diizinkan hanya JPG/PNG
                if (!in_array($extension, ['jpeg', 'jpg', 'png'])) {
                    return response()->json(['message' => 'Format foto tidak diizinkan. Hanya menerima JPG atau PNG.'], 400);
                }

                // Ambil string data base64 yang ada setelah tanda koma
                $base64String = substr($request->foto, strpos($request->foto, ',') + 1);
                
                // Gunakan parameter "true" agar decode lebih ketat (strict mode)
                $decodedImage = base64_decode($base64String, true);

                // 2. Validasi apakah hasil decode berhasil dan tidak rusak
                if ($decodedImage === false) {
                    return response()->json(['message' => 'Data foto rusak (invalid base64).'], 400);
                }

                // 3. Validasi MIME Type menggunakan Fileinfo PHP untuk mencegah file palsu
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_buffer($finfo, $decodedImage);
                finfo_close($finfo);

                if (!in_array($mimeType, ['image/jpeg', 'image/png'])) {
                    return response()->json(['message' => 'File yang diupload bukan gambar yang valid!'], 400);
                }

                // 4. Simpan gambar jika semua layer validasi berhasil dilewati
                // Ekstensi disesuaikan otomatis dari file aslinya
                $filename = 'absensi/' . now()->format('Ymd_His') . '_' . str_replace(' ', '_', $request->nama) . '.' . $extension;
                Storage::disk('public')->put($filename, $decodedImage);
                $absensi->foto_path = $filename;
            } else {
                return response()->json(['message' => 'Format base64 header tidak dikenali.'], 400);
            }
        }

        $absensi->save();

        return response()->json([
            'message' => "{$request->nama} berhasil absen (Hadir).",
            'absensi' => $absensi,
        ]);
    }

    /** GET /absensi/logs?role=Semua — data hari ini untuk tabel log */
    public function logs(Request $request)
    {
        $query = Absensi::whereDate('tanggal', today())
            ->orderByDesc('waktu');

        if ($request->role && $request->role !== 'Semua') {
            $query->where('role_type', $request->role);
        }

        return response()->json(
            $query->get(['tanggal', 'waktu', 'nama', 'role_type', 'status'])
        );
    }

    /** PATCH /absensi/{absensi}/status — ubah status kehadiran (admin) */
    public function updateStatus(Request $request, Absensi $absensi)
    {
        $request->validate([
            'status' => 'required|in:Hadir,Izin,Sakit',
        ]);

        $absensi->update(['status' => $request->status]);

        return response()->json(['message' => "Status {$absensi->nama} diubah jadi {$request->status}."]);
    }

    /** 
     * GET /api/get-new-faces 
     * Endpoint untuk sinkronisasi dataset wajah IoT (Raspberry Pi)
     */
    public function getNewFaces()
    {
        // 1. Ambil dataset dari tabel Siswa
        $siswas = \App\Models\Siswa::whereNotNull('foto_wajah')->get()->map(function ($s) {
            return [
                'name'      => $s->nama,
                'filename'  => basename($s->foto_wajah),
                'image_url' => asset('storage/' . $s->foto_wajah),
            ];
        });

        // 2. Ambil dataset dari tabel Guru
        $gurus = \App\Models\Guru::whereNotNull('foto_wajah')->get()->map(function ($g) {
            return [
                'name'      => $g->nama,
                'filename'  => basename($g->foto_wajah),
                'image_url' => asset('storage/' . $g->foto_wajah),
            ];
        });

        // 3. Gabungkan keduanya
        $users = $siswas->concat($gurus)->values();

        return response()->json([
            'message' => 'Dataset wajah berhasil diambil',
            'users'   => $users
        ]);
    }
}