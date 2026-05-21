<div wire:poll.2s>
    @if($mode === 'siswa' || $mode === 'guru')
        {{-- Kartu Statistik (Kode yang sebelumnya sudah ada) --}}
        <div class="role-section" style="grid-column: 1 / -1; margin-bottom: 2rem;">
            <h3><i class="fas fa-chart-line"></i> Statistik Kehadiran Saya</h3>
            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; margin-top: 1rem;">
                {{-- Kartu Hadir, Izin, Sakit ... --}}
            </div>
        </div>

        {{-- ── TABEL RIWAYAT TERBARU (KHUSUS SISWA) ── --}}
        <div class="card" style="margin-top: 1.5rem; border: 1px solid #E0E0E0; box-shadow: none;">
            <div class="card-header" style="background: #fcfcfc;">
                <h2 style="font-size: 1rem;"><i class="fas fa-history"></i> 10 Riwayat Absensi Terakhir</h2>
            </div>
            <div class="card-body" style="padding: 0;">
                <table class="simple-table">
                    <thead>
                        <tr style="background: #f9f9f9;">
                            <th style="padding-left: 1.5rem;">Tanggal</th>
                            <th>Waktu</th>
                            <th style="text-align: center;">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td style="padding-left: 1.5rem;">{{ $log->tanggal->format('d M Y') }}</td>
                                <td>{{ $log->waktu }}</td>
                                <td style="text-align: center;">
                                    @php
                                        $color = match($log->status) {
                                            'Hadir' => '#27AE60',
                                            'Izin'  => '#F39C12',
                                            'Sakit' => '#E74C3C',
                                            default => '#777'
                                        };
                                    @endphp
                                    <span style="background: {{ $color }}15; color: {{ $color }}; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 0.8rem;">
                                        {{ $log->status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" style="text-align: center; padding: 2rem; color: #aaa;">
                                    Belum ada catatan absensi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        {{-- ── TAMPILAN ADMIN (SEPERTI SEBELUMNYA) ── --}}
        <div class="dashboard-grid">
            <div class="role-section">
                <h3><i class="fas fa-user-graduate"></i> Siswa Hari Ini</h3>
                {{-- (Isi kartu siswa seperti biasa) --}}
                <button class="dash-card" onclick="showDetail('Siswa', 'Hadir')">
                    <div class="dash-icon"><i class="fas fa-user-check"></i></div>
                    <div class="dash-value">{{ $stats['siswa']['hadir'] }}</div>
                    <div class="dash-label">Hadir</div>
                </button>
                {{-- ... sisa tombol siswa ... --}}
            </div>

            <div class="role-section">
                <h3><i class="fas fa-chalkboard-teacher"></i> Guru Hari Ini</h3>
                {{-- (Isi kartu guru seperti biasa) --}}
                <button class="dash-card" onclick="showDetail('Guru', 'Hadir')">
                    <div class="dash-icon"><i class="fas fa-user-check"></i></div>
                    <div class="dash-value">{{ $stats['guru']['hadir'] }}</div>
                    <div class="dash-label">Hadir</div>
                </button>
                {{-- ... sisa tombol guru ... --}}
            </div>
        </div>
    @endif
</div>