@extends('layouts.app')
@section('title', 'Daftar Booking')
@section('page-title', auth()->user()->role === 'mahasiswa' ? 'Booking Saya' : 'Semua Booking')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <h4><i class="bi bi-calendar-check-fill text-primary"></i>
            {{ auth()->user()->role === 'mahasiswa' ? 'Booking Konsultasi Saya' : 'Manajemen Booking' }}
        </h4>
        <p>Total {{ $bookings->total() }} booking ditemukan</p>
    </div>
    @if(auth()->user()->role === 'mahasiswa')
    <a href="{{ route('bookings.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-circle-fill"></i> Booking Baru
    </a>
    @endif
</div>

<!-- FILTER -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['menunggu','dikonfirmasi','selesai','dibatalkan','tidak_hadir'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="topik" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                <option value="">Semua Topik</option>
                @foreach(['Bimbingan Akademik','Tugas Akhir','Revisi Tugas','Konsultasi Nilai','Magang/KP','Lainnya'] as $t)
                <option value="{{ $t }}" {{ request('topik') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
            <input type="text" name="search" class="form-control form-control-sm" placeholder="Cari nama..." value="{{ request('search') }}" style="width:180px">
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-search"></i></button>
            @if(request()->hasAny(['status','topik','search']))
            <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x"></i> Reset</a>
            @endif
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>{{ auth()->user()->role === 'mahasiswa' ? 'Dosen' : 'Mahasiswa' }}</th>
                        <th>Topik</th>
                        <th>Jadwal</th>
                        <th>Lokasi</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($bookings as $b)
                    <tr>
                        <td class="fw-semibold text-primary" style="font-size:12px">{{ $b->kode_booking }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary);font-size:11px;width:30px;height:30px">
                                    {{ strtoupper(substr(auth()->user()->role === 'mahasiswa' ? $b->dosen->name : $b->mahasiswa->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div style="font-size:12px;font-weight:600">
                                        {{ auth()->user()->role === 'mahasiswa' ? $b->dosen->name : $b->mahasiswa->name }}
                                    </div>
                                    <div class="text-muted" style="font-size:10px">
                                        {{ auth()->user()->role === 'mahasiswa' ? $b->dosen->nim_nidn : $b->mahasiswa->nim_nidn }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge-topik badge">{{ $b->topik }}</span></td>
                        <td>
                            <div style="font-size:12px;font-weight:600">
                                {{ \Carbon\Carbon::parse($b->slot->tanggal)->locale('id')->translatedFormat('d M Y') }}
                            </div>
                            <div class="text-muted" style="font-size:11px">
                                {{ substr($b->slot->jam_mulai,0,5) }} – {{ substr($b->slot->jam_selesai,0,5) }}
                            </div>
                        </td>
                        <td style="font-size:12px">{{ $b->slot->lokasi }}</td>
                        <td><span class="badge badge-status-{{ $b->status }}">{{ ucfirst(str_replace('_',' ',$b->status)) }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="{{ route('bookings.show', $b) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @if(in_array($b->status, ['menunggu','dikonfirmasi']))
                                    @if(auth()->user()->role === 'dosen' && $b->status === 'menunggu')
                                    <form method="POST" action="{{ route('bookings.confirm', $b) }}">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    @endif
                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmCancel({{ $b->id }})">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                @endif
                                @if(auth()->user()->role === 'dosen' && $b->status === 'dikonfirmasi')
                                <form method="POST" action="{{ route('bookings.complete', $b) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="btn btn-sm btn-success"><i class="bi bi-check-all"></i> Selesai</button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <p>Tidak ada booking ditemukan</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($bookings->hasPages())
    <div class="card-body border-top py-2">
        {{ $bookings->withQueryString()->links() }}
    </div>
    @endif
</div>

<!-- Modal Batal -->
<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Batalkan Booking</h5></div>
            <form method="POST" id="cancelForm">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <label class="form-label">Alasan pembatalan</label>
                    <textarea name="alasan_batal" class="form-control" rows="2" required placeholder="Masukkan alasan..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger">Ya, Batalkan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function confirmCancel(id) {
    document.getElementById('cancelForm').action = `/bookings/${id}/cancel`;
    new bootstrap.Modal(document.getElementById('cancelModal')).show();
}
</script>
@endpush
