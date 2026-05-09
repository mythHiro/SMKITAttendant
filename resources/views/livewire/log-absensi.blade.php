<div wire:poll.2s>
    <div class="filter-bar" style="margin-bottom: 1rem;">
        <label>Filter:</label>
        <select wire:model.live="filterRole">
            <option value="Semua">Semua</option>
            <option value="Siswa">Siswa</option>
            <option value="Guru">Guru</option>
        </select>
    </div>

    <table class="simple-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Waktu</th>
                <th>Nama</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($logs as $l)
                <tr>
                    <td>{{ $l->tanggal->format('Y-m-d') }}</td>
                    <td>{{ $l->waktu }}</td>
                    <td>{{ $l->nama }}</td>
                    <td>
                        <span style="color: {{ $l->role_type === 'Guru' ? '#4A90E2' : '#27AE60' }}; font-weight: 500;">
                            {{ $l->role_type }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align:center; color:#777;">
                        Belum ada aktivitas hari ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>