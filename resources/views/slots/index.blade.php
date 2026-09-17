@extends('layouts.app')
@section('title','Kelola Slot Jadwal')
@section('page-title','Kelola Slot Jadwal')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-clock-fill text-primary"></i> Kelola Slot Jadwal Konsultasi</h4>
        <p>Tambah dan kelola jadwal konsultasi Anda</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSlotModal">
        <i class="bi bi-plus-circle-fill"></i> Tambah Slot
    </button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Hari</th>
                        <th>Jam Mulai</th>
                        <th>Jam Selesai</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Booking</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($slots as $slot)
                    <tr>
                        <td style="font-size:12px;font-weight:600">{{ \Carbon\Carbon::parse($slot->tanggal)->format('d M Y') }}</td>
                        <td style="font-size:12px">{{ \Carbon\Carbon::parse($slot->tanggal)->locale('id')->translatedFormat('l') }}</td>
                        <td style="font-size:12px">{{ substr($slot->jam_mulai,0,5) }}</td>
                        <td style="font-size:12px">{{ substr($slot->jam_selesai,0,5) }}</td>
                        <td style="font-size:12px">{{ $slot->lokasi }}</td>
                        <td>
                            @php $hasBooking = $slot->bookings->whereIn('status',['menunggu','dikonfirmasi'])->count() > 0; @endphp
                            @if($hasBooking)
                            <span class="badge badge-status-dikonfirmasi">Terisi</span>
                            @else
                            <span class="badge badge-status-selesai">Tersedia</span>
                            @endif
                        </td>
                        <td style="font-size:12px">{{ $slot->bookings->count() }} booking</td>
                        <td>
                            @if(!$hasBooking)
                            <form method="POST" action="{{ route('slots.destroy', $slot) }}" onsubmit="return confirm('Hapus slot ini?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @else
                            <span class="text-muted" style="font-size:11px">Ada booking aktif</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="8"><div class="empty-state"><i class="bi bi-clock-history"></i><p>Belum ada slot jadwal</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($slots->hasPages())
    <div class="card-body border-top py-2">{{ $slots->links() }}</div>
    @endif
</div>

<!-- Modal Tambah Slot -->
<div class="modal fade" id="addSlotModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Slot Jadwal</h5></div>
            <form method="POST" action="{{ route('slots.store') }}">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="tanggal" class="form-control" min="{{ now()->format('Y-m-d') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Jam Mulai</label>
                            <input type="time" name="jam_mulai" class="form-control" value="09:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Jam Selesai</label>
                            <input type="time" name="jam_selesai" class="form-control" value="10:00" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Lokasi</label>
                            <select name="lokasi" class="form-select" required>
                                <option>Ruang Dosen Lt. 2</option>
                                <option>Ruang Dosen Lt. 3</option>
                                <option>Online (Zoom)</option>
                                <option>Ruang Meeting</option>
                                <option>Ruang Kelas</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Simpan Slot</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
