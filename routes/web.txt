<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AbsensiController;
use App\Http\Controllers\SiswaController;
use App\Http\Controllers\GuruController;
use App\Http\Controllers\LaporanController;
use Illuminate\Support\Facades\Route;

// ── Auth ──────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// ── Authenticated (UNTUK MANUSIA DI BROWSER) ──────────
// ── Authenticated (UNTUK MANUSIA DI BROWSER) ──────────
Route::middleware('auth')->group(function () {

    // Ubah rute default menjadi dashboard
    Route::get('/', fn() => redirect()->route('dashboard'));

    // Dashboard (Bisa diakses Admin & Siswa)
    Route::get('/dashboard',        [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stats',  [DashboardController::class, 'stats'])->name('dashboard.stats');
    Route::get('/dashboard/detail', [DashboardController::class, 'detail'])->name('dashboard.detail');

    // ── Admin Only ─────────────────────────────────────
    Route::middleware('admin')->group(function () {
        
        // Kamera Absensi & Log dipindah ke sini
        Route::get('/absensi',          [AbsensiController::class, 'index'])->name('absensi.index');
        Route::post('/absensi',         [AbsensiController::class, 'store'])->name('absensi.store');
        Route::get('/absensi/logs',     [AbsensiController::class, 'logs'])->name('absensi.logs');
        Route::patch('/absensi/{absensi}/status', [AbsensiController::class, 'updateStatus'])->name('absensi.status');

        // Laporan
        Route::get('/laporan',          [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export',   [LaporanController::class, 'export'])->name('laporan.export');

        // CRUD Data
        Route::get('/siswa',            [SiswaController::class, 'index'])->name('siswa.index');
        Route::post('/siswa',           [SiswaController::class, 'store'])->name('siswa.store');
        Route::put('/siswa/{siswa}',    [SiswaController::class, 'update'])->name('siswa.update');
        Route::delete('/siswa/{siswa}', [SiswaController::class, 'destroy'])->name('siswa.destroy');

        Route::get('/guru',             [GuruController::class, 'index'])->name('guru.index');
        Route::post('/guru',            [GuruController::class, 'store'])->name('guru.store');
        Route::put('/guru/{guru}',      [GuruController::class, 'update'])->name('guru.update');
        Route::delete('/guru/{guru}',   [GuruController::class, 'destroy'])->name('guru.destroy');

        // CRUD Kategori (Kelas & Jurusan)
        Route::post('/kategori', [SiswaController::class, 'storeKategori'])->name('kategori.store');
        Route::delete('/kategori/{kategori}', [SiswaController::class, 'destroyKategori'])->name('kategori.destroy');
        
        // Rute untuk Halaman Device Manager
        Route::get('/devices', function () {
            return view('devices.index');
        })->name('devices.index');

        // Rute untuk Download Template Script Python (Universal - dengan Headless Mode)
        Route::get('/devices/template/download', function () {
            $host = request()->getSchemeAndHttpHost();
            
            $pythonCode = <<<PYTHON
import os
import sys
import warnings

# 1. OPTIMASI ENVIRONMENT (Hapus Redundansi)
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '3'
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'
warnings.filterwarnings('ignore')

import logging
logging.getLogger('tensorflow').setLevel(logging.FATAL)

try:
    import absl.logging
    absl.logging.set_verbosity('error')
except ImportError:
    pass

import cv2
import requests
import threading
import time
import shutil
from deepface import DeepFace

# ==========================================
# KONFIGURASI SISTEM
# ==========================================
CAMERA_INDEX = 0  # FIX: Gunakan index konsisten
BASE_URL = "http://192.168.249.131:8000" # Ganti dengan IP Laravel-mu
SYNC_URL = f"{BASE_URL}/api/get-new-faces"
ATTENDANCE_URL = f"{BASE_URL}/api/absensi"

API_KEY = "	sCgI68gSJBMo9IvnjFmvzP3qodsGaxEP".strip()
HEADERS = {
    "X-Device": API_KEY,
    "Accept": "application/json",
    "Content-Type": "application/json"
}
HEADLESS_MODE = False

# FIX: Pastikan folder selalu ada
DATASET_DIR = "dataset"
INTRUDER_DIR = "intruders"
os.makedirs(DATASET_DIR, exist_ok=True)
os.makedirs(INTRUDER_DIR, exist_ok=True)

last_sync_status = ""
last_attendance_sent = {}
unknown_counter = 0
last_intruder_time = 0

# ==========================================
# WORKER: PENGIRIMAN ABSENSI (Mencegah Thread Spam)
# ==========================================
def send_attendance(name):
    try:
        data = {"nama": name, "role_type": "Siswa"}
        response = requests.post(ATTENDANCE_URL, json=data, headers=HEADERS, timeout=10)
        if response.status_code in [200, 201]:
            print(f"[ABSENSI] SUKSES: {name} berhasil diabsen!")
        else:
            print(f"[ABSENSI] Gagal: {response.json().get('message', 'Error')}")
    except Exception as e:
        print(f"[ABSENSI] Error jaringan saat mengirim absen {name}.")

# ==========================================
# WORKER: SINKRONISASI DATA WAJAH
# ==========================================
def sync_faces_with_server():
    global last_sync_status
    while True:
        try:
            response = requests.get(SYNC_URL, headers=HEADERS, timeout=(5, 15))
            if response.status_code == 200:
                data = response.json()
                users_data = data.get('users', [])

                if not users_data and last_sync_status != "empty":
                    print("[SYNC] Tersambung, namun belum ada data wajah di server.")
                    last_sync_status = "empty"
                elif users_data:
                    if last_sync_status not in ["ok", "empty"]:
                        print("[SYNC] Berhasil terhubung ke server.")
                    last_sync_status = "ok"

                new_data_added = False
                data_deleted = False
                server_users = [user['name'] for user in users_data]

                # Download wajah baru
                for user in users_data:
                    user_dir = os.path.join(DATASET_DIR, user['name'])
                    os.makedirs(user_dir, exist_ok=True)
                    img_path = os.path.join(user_dir, user['filename'])
                    
                    if not os.path.exists(img_path):
                        print(f"[SYNC] Mengunduh wajah baru: {user['name']}...")
                        try:
                            img_data = requests.get(user['image_url'], timeout=(2, 10)).content
                            with open(img_path, 'wb') as handler:
                                handler.write(img_data)
                            new_data_added = True
                        except Exception as e:
                            print(f"[SYNC] Gagal mengunduh foto {user['name']}: {e}")

                # Hapus wajah usang
                local_users = [d for d in os.listdir(DATASET_DIR) if os.path.isdir(os.path.join(DATASET_DIR, d))]
                for local_user in local_users:
                    if local_user not in server_users:
                        print(f"[SYNC] Menghapus wajah kadaluarsa: {local_user}...")
                        shutil.rmtree(os.path.join(DATASET_DIR, local_user))
                        data_deleted = True

                # FIX: Penghapusan Cache .pkl yang lebih aman dan mendalam
                if new_data_added or data_deleted:
                    pkl_deleted = False
                    for root, dirs, files in os.walk(DATASET_DIR):
                        for file in files:
                            if file.endswith(".pkl"):
                                os.remove(os.path.join(root, file))
                                pkl_deleted = True
                    if pkl_deleted:
                        print("[SYNC] Cache DeepFace (.pkl) berhasil diperbarui.")

        except requests.exceptions.Timeout:
            if last_sync_status != "timeout":
                print("[SYNC] Gagal: Koneksi timeout.")
                last_sync_status = "timeout"
        except requests.exceptions.ConnectionError:
            if last_sync_status != "disconnected":
                print("[SYNC] Gagal: Tidak ada internet/server mati.")
                last_sync_status = "disconnected"

        time.sleep(60)

# Jalankan Sync di Background
threading.Thread(target=sync_faces_with_server, daemon=True).start()

# ==========================================
# MAIN LOOP: DETEKSI KAMERA & RECOGNITION
# ==========================================
cap = cv2.VideoCapture(CAMERA_INDEX)
cap.set(cv2.CAP_PROP_FRAME_WIDTH, 320)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT, 240)
cap.set(cv2.CAP_PROP_BUFFERSIZE, 1)

frame_count = 0
process_every_n_frames = 10 # FIX: Diubah ke 10 agar tracking lebih smooth
detected_faces = [] # FIX: Cegah current_name saling timpa

print(f"[KAMERA] Sistem Aktif. Mode Headless: {'ON' if HEADLESS_MODE else 'OFF'}")

while True:
    ret, frame = cap.read()
    if not ret:
        print("[KAMERA] Gagal membaca frame. Mencoba reconnect...")
        cap.release()
        time.sleep(2)
        cap = cv2.VideoCapture(CAMERA_INDEX) # FIX: Gunakan index yang sama
        continue

    frame_count += 1
    current_time = time.time()

    # Eksekusi AI hanya setiap n frame
    if frame_count % process_every_n_frames == 0:
        detected_faces = [] # Reset data wajah di frame ini
        
        has_face_folders = any(os.path.isdir(os.path.join(DATASET_DIR, f)) for f in os.listdir(DATASET_DIR))
        
        if has_face_folders:
            try:
                # FIX: enforce_detection=True agar tidak mendeteksi objek acak
                results = DeepFace.find(
                    img_path=frame,
                    db_path=DATASET_DIR,
                    model_name="Facenet",
                    enforce_detection=True, 
                    silent=True
                )

                faces_detected_and_known = False

                for face_match in results:
                    if not face_match.empty:
                        matched_row = face_match.iloc[0]
                        distance = matched_row["distance"]

                        # FIX: Threshold Facenet (Cegah False Positive)
                        if distance > 0.4:
                            continue 

                        faces_detected_and_known = True
                        current_name = os.path.basename(os.path.dirname(matched_row["identity"]))
                        
                        # FIX: Simpan koordinat ke dalam array
                        x, y, w, h = 0, 0, 0, 0
                        if "source_x" in matched_row:
                            x, y, w, h = (
                                int(matched_row["source_x"]), int(matched_row["source_y"]),
                                int(matched_row["source_w"]), int(matched_row["source_h"])
                            )
                        
                        detected_faces.append({
                            "name": current_name,
                            "coords": (x, y, w, h)
                        })

                        # Gembok Waktu Absensi (Cooldown 60 detik per orang)
                        if current_time - last_attendance_sent.get(current_name, 0) > 60:
                            last_attendance_sent[current_name] = current_time 
                            # FIX: Hanya 1 Thread yang dipanggil di sini
                            threading.Thread(target=send_attendance, args=(current_name,), daemon=True).start()

                # LOGIKA INTRUDER
                if faces_detected_and_known:
                    unknown_counter = 0 
                else:
                    unknown_counter += 1
                    if unknown_counter >= 3 and (current_time - last_intruder_time > 15):
                        timestamp = time.strftime("%Y%md_%H%M%S")
                        intruder_filename = os.path.join(INTRUDER_DIR, f"intruder_{timestamp}.jpg")
                        cv2.imwrite(intruder_filename, frame)
                        print(f"[KEAMANAN] Penyusup terekam: {intruder_filename}")
                        last_intruder_time = current_time
                        unknown_counter = 0

            except ValueError:
                # Tidak ada wajah (enforce_detection=True akan melempar error ini jika kosong)
                unknown_counter = 0
            except Exception as e:
                pass

    # RENDER UI (Menggunakan data dari array detected_faces)
    if not HEADLESS_MODE:
        for face in detected_faces:
            x, y, w, h = face["coords"]
            if w > 0:
                cv2.rectangle(frame, (x, y), (x + w, y + h), (0, 255, 0), 2)
                cv2.putText(frame, face["name"], (x, y - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 0), 2)
        
        cv2.imshow("Face Recognition Scanner", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

cap.release()
cv2.destroyAllWindows()
PYTHON;

            return response()->streamDownload(function () use ($pythonCode) {
                echo $pythonCode;
            }, 'iot_attendance_template.py', ['Content-Type' => 'text/x-python']);
            
        })->name('devices.template.download');
    });

}); // <--- PENUTUP GROUP AUTH HARUS DI SINI!


// ── device Only (UNTUK RASPBERRY PI / IOT) ─────────────
Route::middleware('device')->group(function () {
    Route::get('/api/get-new-faces', [AbsensiController::class, 'getNewFaces']);
    Route::post('/api/absensi', [AbsensiController::class, 'store']);
});

// ── Rute Sementara (Bisa diakses siapa saja untuk testing) ──
Route::get('/generate-device', function () {
    $key = \Illuminate\Support\Str::random(32);
    \App\Models\Device::create([
        'name' => 'Kamera Pintu Depan', 
        'api_key' => $key, 
        'is_active' => true
    ]);
    return "Berhasil! KODE RAHASIA DEVICE ANDA: <strong>" . $key . "</strong>";
});