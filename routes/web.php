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
import signal
import warnings
import logging
import gc

# ============================================================
#  OPTIMASI ENVIRONMENT — Wajib sebelum import TF/DeepFace
# ============================================================
os.environ['TF_CPP_MIN_LOG_LEVEL']  = '3'
os.environ['TF_ENABLE_ONEDNN_OPTS'] = '0'
warnings.filterwarnings('ignore')
logging.getLogger('tensorflow').setLevel(logging.FATAL)
logging.getLogger('deepface').setLevel(logging.FATAL)

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
from collections import deque, Counter
from deepface import DeepFace

# ============================================================
#  KONFIGURASI — Edit bagian ini saja
# ============================================================

# --- Jaringan ---
BASE_URL        = "http://127.0.0.1:8000"
SYNC_URL        = f"{BASE_URL}/api/get-new-faces"
ATTENDANCE_URL  = f"{BASE_URL}/api/absensi"
API_KEY         = "" # ISI BAGIAN INI DENGAN X-Device-Key yang sudah di generate!!!!
HEADERS         = {
    "X-Device-Key": API_KEY,  # <--- INI YANG DIPERBAIKI
    "Accept": "application/json",
    "Content-Type": "application/json",
}

# --- Kamera ---
#   CAMERA_KEYWORD: nama (sebagian) device yang dicari otomatis.
#   Jika None atau tidak ditemukan → pakai CAMERA_INDEX_FALLBACK.
#   Contoh keyword lain: "Logitech", "Integrated", "USB Camera"
CAMERA_KEYWORD          = "OBSBOT"
CAMERA_INDEX_FALLBACK   = 0
CAM_WIDTH               = 1280   # 720p
CAM_HEIGHT              = 720
HEADLESS_MODE           = False   # True = tanpa jendela (mode server/Pi headless)

# --- Direktori ---
DATASET_DIR     = "dataset"
INTRUDER_DIR    = "intruders"

# --- Model AI ---
#   Facenet512 lebih akurat untuk dataset banyak orang.
#   Threshold cosine: 0.30 = default DeepFace (ketat). Naikkan s.d. 0.40 jika
#   sering false-negative (orang yang terdaftar tidak dikenali).
MODEL_NAME          = "Facenet512"
DISTANCE_METRIC     = "cosine"
DISTANCE_THRESHOLD  = 0.35

#   DETECTOR_CHAIN: urutan detector yang dicoba per-frame.
#   Sistem akan otomatis turun ke level berikutnya jika detector gagal deteksi.
#
#   "retinaface" → akurasi tertinggi, support angle ekstrem  (butuh pip install retina-face)
#   "mtcnn"      → bagus, lebih ringan dari retinaface        (butuh pip install mtcnn)
#   "opencv"     → paling ringan, kadang "buta" angle miring
#
#   Rekomendasi laptop/PC kuat: ["retinaface", "mtcnn", "opencv"]
#   Rekomendasi Raspberry Pi   : ["mtcnn", "opencv"]
DETECTOR_CHAIN  = ["retinaface", "mtcnn", "opencv"]

# --- FaceTracker (Sistem Voting Multi-Frame) ---
VOTE_WINDOW             = 6     # Ukuran sliding window (frame)
MIN_VOTES_TO_CONFIRM    = 3     # Minimal vote sebelum identitas dikunci
KNOWN_CONFIRM_RATIO     = 0.60  # ≥60% vote sepakat → "dikenal"
UNKNOWN_CONFIRM_RATIO   = 0.55  # ≥55% vote unknown → "tidak terdaftar"
TRACK_TIMEOUT_SEC       = 4.0   # Track dihapus jika tidak terlihat N detik
IOU_MATCH_THRESHOLD     = 0.25  # Minimal IoU untuk mencocokkan track ke deteksi baru

# --- Performa & Keamanan ---
ATTENDANCE_COOLDOWN     = 60    # Detik jeda absensi per orang
INTRUDER_COOLDOWN       = 15    # Detik jeda foto penyusup
SYNC_INTERVAL           = 60    # Detik antar sinkronisasi server
PROCESS_EVERY_N_FRAMES  = 8     # Jalankan AI setiap N frame
MAX_COOLDOWN_ENTRIES    = 500   # Batas entry cooldown (cegah memory leak)

# ============================================================
os.makedirs(DATASET_DIR, exist_ok=True)
os.makedirs(INTRUDER_DIR, exist_ok=True)

# ============================================================
#  AUTO-DETECT KAMERA
# ============================================================

def list_cameras_windows() -> dict[int, str]:
    """
    Baca nama device kamera di Windows menggunakan pygrabber.
    Return: {index: nama_device}
    """
    try:
        from pygrabber.dshow_graph import FilterGraph
        graph   = FilterGraph()
        devices = graph.get_input_devices()
        return {i: name for i, name in enumerate(devices)}
    except ImportError:
        return {}
    except Exception:
        return {}


def find_camera_index(keyword: str | None, fallback: int) -> tuple[int, str]:
    """
    Cari kamera berdasarkan keyword nama device.
    Return: (index, nama_device)

    Langkah:
      1. Coba baca nama device via pygrabber (Windows).
      2. Jika tidak bisa, scan index 0–9 dan cek yang bisa dibuka.
      3. Fallback ke CAMERA_INDEX_FALLBACK.
    """
    if keyword:
        # Coba via pygrabber (nama asli device)
        devices = list_cameras_windows()
        if devices:
            print("[KAMERA] Device yang terdeteksi:")
            for idx, name in devices.items():
                mark = "  ← target" if keyword.lower() in name.lower() else ""
                print(f"         [{idx}] {name}{mark}")

            for idx, name in devices.items():
                if keyword.lower() in name.lower():
                    print(f"[KAMERA] ✓ '{name}' dipilih (index {idx})")
                    return idx, name

            print(f"[KAMERA] ⚠ Keyword '{keyword}' tidak ditemukan di device list.")

        else:
            # pygrabber tidak tersedia → scan manual + print info
            print("[KAMERA] pygrabber tidak tersedia, scan kamera manual...")
            print("[KAMERA] Install pygrabber untuk auto-detect nama: pip install pygrabber")
            found = []
            for i in range(8):
                cap = cv2.VideoCapture(i, cv2.CAP_DSHOW)
                if cap.isOpened():
                    found.append(i)
                    cap.release()
            if found:
                print(f"[KAMERA] Index kamera yang terbuka: {found}")
                print(f"[KAMERA] Tidak bisa baca nama device. Pakai CAMERA_INDEX_FALLBACK = {fallback}")

    print(f"[KAMERA] Menggunakan fallback index: {fallback}")
    return fallback, f"Camera [{fallback}]"


# ============================================================
#  DATASET VALIDATOR
# ============================================================

def validate_dataset_background():
    """
    Scan semua gambar di dataset, coba deteksi wajah.
    Jalankan di background thread saat startup agar tidak block program.
    Gambar bermasalah hanya di-warning, tidak dihapus otomatis.
    """
    def _run():
        print("[DATASET] Memulai validasi dataset...")
        bad   = []
        total = 0

        for person in sorted(os.listdir(DATASET_DIR)):
            person_dir = os.path.join(DATASET_DIR, person)
            if not os.path.isdir(person_dir):
                continue

            imgs = [
                f for f in os.listdir(person_dir)
                if f.lower().endswith((".jpg", ".jpeg", ".png", ".webp"))
            ]

            for img_file in imgs:
                total += 1
                img_path = os.path.join(person_dir, img_file)

                ok = False
                # Coba dengan setiap detector di chain
                for detector in DETECTOR_CHAIN:
                    try:
                        DeepFace.extract_faces(
                            img_path         = img_path,
                            detector_backend = detector,
                            enforce_detection = True,
                        )
                        ok = True
                        break   # Berhasil → tidak perlu coba detector berikutnya
                    except ValueError:
                        continue    # Detector ini gagal, coba berikutnya
                    except Exception:
                        continue

                if not ok:
                    bad.append(img_path)

        if not bad:
            print(f"[DATASET] ✓ Semua {total} gambar valid (wajah terdeteksi).")
        else:
            print(f"[DATASET] ⚠ {len(bad)}/{total} gambar bermasalah (wajah tidak terdeteksi):")
            for p in bad:
                print(f"          → {p}")
            print("[DATASET]   Saran: ganti foto tsb dengan foto wajah yang lebih jelas,")
            print("[DATASET]   terang, dan menghadap depan.")

    threading.Thread(target=_run, daemon=True, name="DatasetValidator").start()


# ============================================================
#  FACE TRACKER
# ============================================================

def compute_iou(b1: tuple, b2: tuple) -> float:
    x1, y1, w1, h1 = b1
    x2, y2, w2, h2 = b2
    inter_x = max(0, min(x1 + w1, x2 + w2) - max(x1, x2))
    inter_y = max(0, min(y1 + h1, y2 + h2) - max(y1, y2))
    inter   = inter_x * inter_y
    union   = w1 * h1 + w2 * h2 - inter
    return inter / union if union > 0 else 0.0


class FaceTrack:
    """
    Melacak satu wajah lintas frame.
    Identitas dikunci via voting sliding window — bukan dari 1 frame tunggal.

    Status:
      "pending"       — belum cukup vote
      "dikenal"       — terkonfirmasi ada di dataset
      "tidak_dikenal" — terkonfirmasi tidak terdaftar
    """
    _counter = 0

    def __init__(self, bbox, name, known, dist):
        FaceTrack._counter += 1
        self.id             = FaceTrack._counter
        self.bbox           = bbox
        self.votes          = deque(maxlen=VOTE_WINDOW)
        self.last_seen      = time.time()
        self.confirmed_name = None
        self.status         = "pending"
        self.confidence     = 0.0
        self._add_vote(name, known, dist)

    def _add_vote(self, name, known, dist):
        self.votes.append((name, known, dist))
        self.last_seen = time.time()
        self._recompute()

    def update(self, bbox, name, known, dist):
        self.bbox = bbox
        self._add_vote(name, known, dist)

    def touch(self, bbox):
        """Update posisi tanpa vote baru (frame tanpa rekognisi)."""
        self.bbox       = bbox
        self.last_seen  = time.time()

    def _recompute(self):
        n = len(self.votes)
        if n < MIN_VOTES_TO_CONFIRM:
            self.status     = "pending"
            self.confidence = 0.0
            return

        known_votes   = [(nm, d) for nm, k, d in self.votes if k]
        unknown_count = sum(1 for _, k, _ in self.votes if not k)

        if len(known_votes) / n >= KNOWN_CONFIRM_RATIO:
            best, cnt = Counter(nm for nm, _ in known_votes).most_common(1)[0]
            self.confirmed_name = best
            self.confidence     = cnt / n
            self.status         = "dikenal"
        elif unknown_count / n >= UNKNOWN_CONFIRM_RATIO:
            self.confirmed_name = "TIDAK TERDAFTAR"
            self.confidence     = unknown_count / n
            self.status         = "tidak_dikenal"
        else:
            self.status         = "pending"
            self.confidence     = 0.0

    def is_stale(self) -> bool:
        return time.time() - self.last_seen > TRACK_TIMEOUT_SEC

    @property
    def label(self) -> str:
        if self.status == "dikenal":
            return f"{self.confirmed_name} ({self.confidence*100:.0f}%)"
        elif self.status == "tidak_dikenal":
            return f"TIDAK TERDAFTAR ({self.confidence*100:.0f}%)"
        return "Mengidentifikasi..."

    @property
    def color(self) -> tuple:
        """BGR color untuk bounding box."""
        if self.status == "dikenal":        return (30, 200, 30)   # Hijau
        elif self.status == "tidak_dikenal": return (30, 30, 210)  # Merah
        return (30, 180, 240)                                       # Kuning (pending)


class FaceTrackerManager:
    def __init__(self):
        self._tracks: dict[int, FaceTrack] = {}
        self._lock = threading.Lock()

    def update(self, detections: list) -> list[FaceTrack]:
        with self._lock:
            # Bersihkan track stale
            for tid in [t for t, tr in self._tracks.items() if tr.is_stale()]:
                del self._tracks[tid]

            matched_tracks = set()
            matched_dets   = set()

            # Cocokkan deteksi ke track yang ada (IoU)
            for tid, track in self._tracks.items():
                best_iou, best_i = IOU_MATCH_THRESHOLD, -1
                for i, det in enumerate(detections):
                    if i in matched_dets:
                        continue
                    iou = compute_iou(track.bbox, det["bbox"])
                    if iou > best_iou:
                        best_iou, best_i = iou, i
                if best_i >= 0:
                    d = detections[best_i]
                    track.update(d["bbox"], d["name"], d["known"], d["dist"])
                    matched_tracks.add(tid)
                    matched_dets.add(best_i)

            # Deteksi baru yang tidak cocok ke track mana pun → track baru
            for i, d in enumerate(detections):
                if i not in matched_dets:
                    t = FaceTrack(d["bbox"], d["name"], d["known"], d["dist"])
                    self._tracks[t.id] = t

            return list(self._tracks.values())

    def get_all(self) -> list[FaceTrack]:
        with self._lock:
            return list(self._tracks.values())


# ============================================================
#  DETECTOR DENGAN FALLBACK CHAIN
# ============================================================

_last_working_detector = DETECTOR_CHAIN[0]

def deepface_find_with_fallback(frame) -> tuple[list, str]:
    """
    Coba DeepFace.find dengan setiap detector di DETECTOR_CHAIN.
    Mulai dari detector yang terakhir berhasil (cache), bukan selalu dari awal.

    Return: (results, detector_yang_dipakai)
    Raise ValueError  jika semua detector tidak menemukan wajah.
    Raise RuntimeError jika semua detector error bukan karena "no face".
    """
    global _last_working_detector

    # Susun urutan: detektor terakhir yang berhasil didahulukan
    chain = [_last_working_detector] + [
        d for d in DETECTOR_CHAIN if d != _last_working_detector
    ]

    last_exc = None
    for detector in chain:
        try:
            results = DeepFace.find(
                img_path            = frame,
                db_path             = DATASET_DIR,
                model_name          = MODEL_NAME,
                distance_metric     = DISTANCE_METRIC,
                detector_backend    = detector,
                enforce_detection   = True,
                silent              = True,
            )
            _last_working_detector = detector   # Simpan yang berhasil
            return results, detector

        except ValueError as e:
            # "Face could not be detected" — tidak ada wajah di frame ini
            # Ini bukan error program, cukup return kosong
            raise ValueError(str(e)) from None

        except Exception as e:
            # Detector gagal karena alasan lain (model belum didownload, dll)
            last_exc = e
            continue

    # Semua detector gagal
    raise RuntimeError(
        f"Semua detector gagal. Error terakhir: {last_exc}"
    )


# ============================================================
#  STATE GLOBAL
# ============================================================
shutdown_event  = threading.Event()
state_lock      = threading.Lock()
dataset_lock    = threading.RLock()

last_sync_status            = ""
last_attendance_sent: dict  = {}
last_intruder_time          = 0.0

recognition_result: list    = []
recognition_running         = False
active_detector_display     = DETECTOR_CHAIN[0]  # Ditampilkan di HUD

tracker = FaceTrackerManager()

# ============================================================
#  GRACEFUL SHUTDOWN
# ============================================================
def handle_shutdown(signum, _frame):
    print("\n[SISTEM] Shutdown...")
    shutdown_event.set()

signal.signal(signal.SIGINT,  handle_shutdown)
signal.signal(signal.SIGTERM, handle_shutdown)

# ============================================================
#  WORKER: ABSENSI
# ============================================================
def _do_send(name: str):
    try:
        r = requests.post(
            ATTENDANCE_URL,
            json    = {"nama": name, "role_type": "Siswa"},
            headers = HEADERS,
            timeout = 10,
        )
        if r.status_code in (200, 201):
            print(f"[ABSENSI] ✓ {name}")
        else:
            print(f"[ABSENSI] ✗ {name} → {r.json().get('message', r.status_code)}")
    except requests.exceptions.Timeout:
        print(f"[ABSENSI] ✗ Timeout: {name}")
    except requests.exceptions.ConnectionError:
        print(f"[ABSENSI] ✗ Server tidak terjangkau: {name}")
    except Exception as e:
        print(f"[ABSENSI] ✗ Error: {e}")


def try_send_attendance(name: str):
    now = time.time()
    with state_lock:
        if now - last_attendance_sent.get(name, 0) <= ATTENDANCE_COOLDOWN:
            return
            
        last_attendance_sent[name] = now
        
        # Jauh lebih ringan: Hapus 50 entri terlama tanpa di-sort
        if len(last_attendance_sent) > MAX_COOLDOWN_ENTRIES:
            for _ in range(50):
                # Ambil kunci paling pertama (paling lama dimasukkan) lalu hapus
                oldest_key = next(iter(last_attendance_sent))
                del last_attendance_sent[oldest_key]
                
    threading.Thread(target=_do_send, args=(name,), daemon=True).start()


# ============================================================
#  WORKER: SINKRONISASI DATASET
# ============================================================
def sync_faces_with_server():
    global last_sync_status
    while not shutdown_event.is_set():
        try:
            resp       = requests.get(SYNC_URL, headers=HEADERS, timeout=(5, 15))
            users_data = resp.json().get("users", []) if resp.status_code == 200 else []

            with dataset_lock:
                if resp.status_code != 200:
                    print(f"[SYNC] Kode server: {resp.status_code}")
                else:
                    server_names = {u["name"] for u in users_data}
                    new_added = deleted = False

                    if not users_data and last_sync_status != "empty":
                        print("[SYNC] Tersambung, belum ada data wajah di server.")
                        last_sync_status = "empty"
                    elif users_data and last_sync_status not in ("ok", "empty"):
                        print("[SYNC] Terhubung ke server.")

                    # Download wajah baru
                    for u in users_data:
                        udir = os.path.join(DATASET_DIR, u["name"])
                        os.makedirs(udir, exist_ok=True)
                        ipath = os.path.join(udir, u["filename"])
                        if not os.path.exists(ipath):
                            print(f"[SYNC] Unduh: {u['name']}...")
                            try:
                                data = requests.get(u["image_url"], timeout=(2, 10)).content
                                with open(ipath, "wb") as f:
                                    f.write(data)
                                new_added = True
                            except Exception as e:
                                print(f"[SYNC] Gagal unduh {u['name']}: {e}")

                    # Hapus kadaluarsa
                    for local in os.listdir(DATASET_DIR):
                        lpath = os.path.join(DATASET_DIR, local)
                        if os.path.isdir(lpath) and local not in server_names:
                            print(f"[SYNC] Hapus kadaluarsa: {local}")
                            shutil.rmtree(lpath)
                            deleted = True

                    # Bersihkan cache .pkl
                    if new_added or deleted:
                        n = sum(
                            1 for r, _, fs in os.walk(DATASET_DIR)
                            for f in fs if f.endswith(".pkl")
                            for _ in [os.remove(os.path.join(r, f))]
                        )
                        if n:
                            print(f"[SYNC] Cache diperbarui ({n} .pkl dihapus).")

                    if users_data:
                        last_sync_status = "ok"

        except requests.exceptions.Timeout:
            if last_sync_status != "timeout":
                print("[SYNC] Timeout.")
                last_sync_status = "timeout"
        except requests.exceptions.ConnectionError:
            if last_sync_status != "disconnected":
                print("[SYNC] Server tidak terjangkau.")
                last_sync_status = "disconnected"
        except Exception as e:
            print(f"[SYNC] Error: {e}")

        shutdown_event.wait(timeout=SYNC_INTERVAL)


# ============================================================
#  WORKER: REKOGNISI WAJAH
# ============================================================
def run_recognition(frame_copy):
    global recognition_result, recognition_running, last_intruder_time, active_detector_display

    try:
        scale_factor = 0.5 
        small_frame = cv2.resize(frame_copy, (0, 0), fx=scale_factor, fy=scale_factor)

        with dataset_lock:
            has_data = any(
                os.path.isdir(os.path.join(DATASET_DIR, d))
                for d in os.listdir(DATASET_DIR)
            )
        if not has_data:
            tracker.update([])
            with state_lock:
                recognition_result  = []
                recognition_running = False
            return

        # --- Rekognisi dengan fallback detector ---
        with dataset_lock:
            results, used_detector = deepface_find_with_fallback(small_frame)

        with state_lock:
            active_detector_display = used_detector

        raw_dets    = []
        current_time = time.time()

        for face_df in results:
            x = y = w = h = 0

            if not face_df.empty:
                row = face_df.iloc[0]
                if "source_x" in row.index:
                    # --- BAGIAN YANG DIUBAH ---
                    # Karena sebelumnya gambar diperkecil (misal scale_factor = 0.5), 
                    # maka koordinat X, Y, W, H dari AI juga ikut mengecil.
                    # Kita harus membaginya dengan scale_factor agar ukurannya 
                    # kembali besar dan pas di gambar aslinya.
                    x = int(row["source_x"] / scale_factor)
                    y = int(row["source_y"] / scale_factor)
                    w = int(row["source_w"] / scale_factor)
                    h = int(row["source_h"] / scale_factor)
                    # --------------------------
                    
                dist  = float(row["distance"])
                known = dist <= DISTANCE_THRESHOLD
                name  = (
                    os.path.basename(os.path.dirname(str(row["identity"])))
                    if known else "TIDAK TERDAFTAR"
                )
                if not known:
                    dist = 1.0
            else:
                name, known, dist = "TIDAK TERDAFTAR", False, 1.0   

            raw_dets.append({"bbox": (x, y, w, h), "name": name, "known": known, "dist": dist})

        # Update tracker
        active_tracks = tracker.update(raw_dets)

        # Buat data render + kirim absensi
        render = []
        for t in active_tracks:
            render.append({"bbox": t.bbox, "label": t.label, "color": t.color, "status": t.status})
            if t.status == "dikenal" and t.confirmed_name:
                try_send_attendance(t.confirmed_name)

        # Foto penyusup (hanya jika track TERKONFIRMASI tidak terdaftar)
        with state_lock:
            has_intruder = any(t.status == "tidak_dikenal" for t in active_tracks)
            if has_intruder and (current_time - last_intruder_time > INTRUDER_COOLDOWN):
                ts   = time.strftime("%Y%m%d_%H%M%S")
                path = os.path.join(INTRUDER_DIR, f"intruder_{ts}.jpg")
                cv2.imwrite(path, frame_copy)
                print(f"[KEAMANAN] ⚠ Penyusup terekam: {path}")
                last_intruder_time = current_time

        with state_lock:
            recognition_result = render

    except ValueError:
        # Tidak ada wajah di frame — ini normal, bukan error
        tracker.update([])
        with state_lock:
            recognition_result = []

    except RuntimeError as e:
        # Semua detector di chain gagal (bukan karena tidak ada wajah)
        print(f"[RECOGNITION] ⚠ {e}")
        with state_lock:
            recognition_result = []

    except Exception as e:
        print(f"[RECOGNITION] Error: {type(e).__name__}: {e}")
        with state_lock:
            recognition_result = []

    finally:
        with state_lock:
            recognition_running = False
        gc.collect()

# ============================================================
#  RENDER HELPER
# ============================================================
def draw_face_box(frame, bbox, label, color):
    x, y, w, h = bbox
    if w <= 0 or h <= 0:
        return

    cv2.rectangle(frame, (x, y), (x + w, y + h), color, 2)

    font        = cv2.FONT_HERSHEY_SIMPLEX
    fscale      = 0.55
    thick       = 1
    (tw, th), _ = cv2.getTextSize(label, font, fscale, thick)
    ly          = y - 10 if y - 10 > 20 else y + h + 22

    cv2.rectangle(frame, (x, ly - th - 6), (x + tw + 8, ly + 2), color, -1)
    cv2.putText(frame, label, (x + 4, ly - 3), font, fscale,
                (255, 255, 255), thick, cv2.LINE_AA)


def draw_hud(frame, sync_status, track_count, detector_name):
    h, _ = frame.shape[:2]
    s_color = (30, 200, 30) if sync_status == "ok" else (30, 120, 255)
    cv2.putText(frame, f"Server: {sync_status or 'init'}",
                (8, 22), cv2.FONT_HERSHEY_SIMPLEX, 0.5, s_color, 1, cv2.LINE_AA)
    cv2.putText(frame, f"Wajah terdeteksi: {track_count}",
                (8, 44), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (200, 200, 200), 1, cv2.LINE_AA)
    cv2.putText(frame, f"{MODEL_NAME} | detector: {detector_name} | thr={DISTANCE_THRESHOLD}",
                (8, h - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.42, (120, 120, 120), 1, cv2.LINE_AA)


# ============================================================
#  INISIALISASI
# ============================================================

# 1. Cari kamera OBSBOT (atau fallback)
cam_index, cam_name = find_camera_index(CAMERA_KEYWORD, CAMERA_INDEX_FALLBACK)

# 2. Buka kamera
cap = cv2.VideoCapture(cam_index, cv2.CAP_DSHOW)   # CAP_DSHOW lebih stabil di Windows
cap.set(cv2.CAP_PROP_FRAME_WIDTH,   CAM_WIDTH)
cap.set(cv2.CAP_PROP_FRAME_HEIGHT,  CAM_HEIGHT)
cap.set(cv2.CAP_PROP_BUFFERSIZE,    1)

if not cap.isOpened():
    print(f"[KAMERA] FATAL: Tidak dapat membuka '{cam_name}' (index {cam_index}).")
    sys.exit(1)

actual_w = int(cap.get(cv2.CAP_PROP_FRAME_WIDTH))
actual_h = int(cap.get(cv2.CAP_PROP_FRAME_HEIGHT))

print(f"\n[SISTEM] ═══ Face-Attendant ULTIMATE v3 ═══")
print(f"[SISTEM] Kamera   : {cam_name} (index {cam_index})")
print(f"[SISTEM] Resolusi : {actual_w}×{actual_h}")
print(f"[SISTEM] Model    : {MODEL_NAME} ({DISTANCE_METRIC}, threshold={DISTANCE_THRESHOLD})")
print(f"[SISTEM] Detector : {' → '.join(DETECTOR_CHAIN)} (fallback otomatis)")
print(f"[SISTEM] Voting   : window={VOTE_WINDOW} frame, confirm={MIN_VOTES_TO_CONFIRM}")
print(f"[SISTEM] Headless : {'ON' if HEADLESS_MODE else 'OFF'} | Tekan 'q' untuk keluar.\n")

# 3. Jalankan background threads
threading.Thread(target=sync_faces_with_server, daemon=True, name="SyncThread").start()
validate_dataset_background()   # Scan dataset, warning jika ada gambar bermasalah

frame_count = 0

# ============================================================
#  MAIN LOOP
# ============================================================
while not shutdown_event.is_set():
    ret, frame = cap.read()

    if not ret:
        print("[KAMERA] Gagal baca frame. Reconnect...")
        cap.release()
        shutdown_event.wait(timeout=2)
        if shutdown_event.is_set():
            break
        cap = cv2.VideoCapture(cam_index, cv2.CAP_DSHOW)
        cap.set(cv2.CAP_PROP_FRAME_WIDTH,  CAM_WIDTH)
        cap.set(cv2.CAP_PROP_FRAME_HEIGHT, CAM_HEIGHT)
        cap.set(cv2.CAP_PROP_BUFFERSIZE,   1)
        if not cap.isOpened():
            print("[KAMERA] Reconnect gagal, coba lagi 5 detik...")
            shutdown_event.wait(timeout=5)
        continue

    frame_count += 1

    # Spawn thread rekognisi setiap N frame (non-blocking)
    with state_lock:
        can_run = (frame_count % PROCESS_EVERY_N_FRAMES == 0) and not recognition_running

    if can_run:
        with state_lock:
            recognition_running = True
        threading.Thread(
            target = run_recognition,
            args   = (frame.copy(),),
            daemon = True,
            name   = "RecognitionThread",
        ).start()

    # Render
    if not HEADLESS_MODE:
        with state_lock:
            to_render    = list(recognition_result)
            sync_stat    = last_sync_status
            cur_detector = active_detector_display

        for face in to_render:
            draw_face_box(frame, face["bbox"], face["label"], face["color"])

        draw_hud(frame, sync_stat, len(to_render), cur_detector)

        cv2.imshow("Face-Attendant v3", frame)
        if cv2.waitKey(1) & 0xFF == ord('q'):
            shutdown_event.set()
            break

# ============================================================
#  CLEANUP
# ============================================================
print("[SISTEM] Menutup sistem...")
cap.release()
cv2.destroyAllWindows()
print("[SISTEM] Selesai.")

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

Route::get('/sync-users-lama', function () {
    $countSiswa = 0;
    $countGuru = 0;

    // 1. Loop semua data Siswa lama
    $siswas = \App\Models\Siswa::all();
    foreach ($siswas as $s) {
        // firstOrCreate memastikan kalau akun sudah ada, tidak akan dibuat dobel
        $user = \App\Models\User::firstOrCreate(
            ['username' => strtolower($s->nis)],
            [
                'name'     => $s->nama,
                'password' => \Illuminate\Support\Facades\Hash::make($s->nis),
                'role'     => 'siswa'
            ]
        );
        if ($user->wasRecentlyCreated) $countSiswa++;
    }

    // 2. Loop semua data Guru lama
    $gurus = \App\Models\Guru::all();
    foreach ($gurus as $g) {
        $user = \App\Models\User::firstOrCreate(
            ['username' => strtolower($g->nip)],
            [
                'name'     => $g->nama,
                'password' => \Illuminate\Support\Facades\Hash::make($g->nip),
                'role'     => 'guru'
            ]
        );
        if ($user->wasRecentlyCreated) $countGuru++;
    }

    return "Sinkronisasi Selesai! Berhasil membuat {$countSiswa} akun siswa lama dan {$countGuru} akun guru lama.";
});