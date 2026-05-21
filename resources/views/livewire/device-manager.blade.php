<div>
    @if (session()->has('message'))
        <div style="background: #27AE60; color: white; padding: 10px; border-radius: 6px; margin-bottom: 1rem;">
            <i class="fas fa-check-circle"></i> {{ session('message') }}
        </div>
    @endif

    <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
        <div>
            @if($isAdding)
                <div style="display: flex; gap: 0.5rem; align-items: center;">
                    <input type="text" wire:model="name" placeholder="Nama Device (cth: Kamera Lobi Utama)" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 6px; width: 300px;">
                    <button wire:click="generateDevice" class="btn btn-success"><i class="fas fa-save"></i> Generate Key</button>
                    <button wire:click="$set('isAdding', false)" class="btn btn-danger">Batal</button>
                </div>
                @error('name') <span style="color: red; font-size: 0.8rem;">Nama device wajib diisi!</span> @enderror
            @else
                <button wire:click="$set('isAdding', true)" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Tambah Perangkat Baru
                </button>
            @endif
        </div>

        {{-- TOMBOL DOWNLOAD TEMPLATE GLOBAL --}}
        <a href="{{ route('devices.template.download') }}" class="btn" style="background: #2C3E50; color: white; text-decoration: none;">
            <i class="fab fa-python"></i> Download Template Python
        </a>
    </div>

    <table class="simple-table">
        <thead>
            <tr>
                <th>Nama Perangkat</th>
                <th>API Key (X-Device-Key)</th>
                <th>Koneksi IoT</th>
                <th>Akses Alat</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            @forelse($devices as $device)
                <tr>
                    <td><strong>{{ $device->name }}</strong></td>
                    <td><code style="background: #f4f4f4; padding: 4px 8px; border-radius: 4px; color: #E74C3C; font-weight: bold;">{{ $device->api_key }}</code></td>
                    
                    {{-- KOLOM STATUS KONEKSI (BARU) --}}
                    <td>
                        {{-- Jika perangkat pernah terhubung dan waktu terakhirnya kurang dari 3 menit yang lalu --}}
                        @if($device->last_seen && $device->last_seen->diffInMinutes(now()) < 3)
                            <span class="badge badge-green">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 4px; animation: blink 1.5s infinite;"></i> Online
                            </span>
                        @else
                            <span class="badge badge-red">
                                <i class="fas fa-circle" style="font-size: 8px; margin-right: 4px;"></i> Offline
                            </span>
                            <div style="font-size: 0.7rem; color: #888; margin-top: 4px;">
                                {{ $device->last_seen ? 'Terakhir: ' . $device->last_seen->diffForHumans() : 'Belum pernah sinkronisasi' }}
                            </div>
                        @endif
                    </td>

                    <td>
                        <button wire:click="toggleStatus({{ $device->id }})" class="btn btn-sm {{ $device->is_active ? 'btn-success' : 'btn-warning' }}" style="width: 85px;">
                            <i class="fas {{ $device->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i> {{ $device->is_active ? 'Aktif' : 'Nonaktif' }}
                        </button>
                    </td>
                    <td>
                        <button wire:click="deleteDevice({{ $device->id }})" class="btn btn-danger btn-sm" onclick="confirm('Yakin ingin menghapus perangkat ini?') || event.stopImmediatePropagation()">
                            <i class="fas fa-trash"></i> Hapus
                        </button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align: center; color: #777;">Belum ada perangkat IoT yang terdaftar.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>