@extends('layouts.app')
@section('title','Detail Booking')
@section('page-title','Detail Booking')
@section('content')
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h4><i class="bi bi-calendar-event text-primary"></i> Detail Booking</h4>
        <p>Kode: <strong class="text-primary">{{ $booking->kode_booking }}</strong></p>
    </div>
    <a href="{{ url()->previous() }}" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
</div>

<div class="row g-3">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle-fill"></i> Informasi Booking</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;font-weight:600">MAHASISWA</div>
                        <div class="fw-semibold mt-1">{{ $booking->mahasiswa->name }}</div>
                        <div class="text-muted" style="font-size:12px">{{ $booking->mahasiswa->nim_nidn }} · {{ $booking->mahasiswa->jurusan }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;font-weight:600">DOSEN</div>
                        <div class="fw-semibold mt-1">{{ $booking->dosen->name }}</div>
                        <div class="text-muted" style="font-size:12px">{{ $booking->dosen->nim_nidn }} · {{ $booking->dosen->jurusan }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;font-weight:600">TOPIK</div>
                        <div class="mt-1"><span class="badge-topik badge">{{ $booking->topik }}</span></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;font-weight:600">STATUS</div>
                        <div class="mt-1"><span class="badge badge-status-{{ $booking->status }}">{{ ucfirst(str_replace('_',' ',$booking->status)) }}</span></div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;font-weight:600">TANGGAL & WAKTU</div>
                        <div class="fw-semibold mt-1">{{ \Carbon\Carbon::parse($booking->slot->tanggal)->locale('id')->translatedFormat('l, d F Y') }}</div>
                        <div class="text-muted" style="font-size:12px">{{ substr($booking->slot->jam_mulai,0,5) }} – {{ substr($booking->slot->jam_selesai,0,5) }}</div>
                    </div>
                    <div class="col-6">
                        <div class="text-muted" style="font-size:11px;font-weight:600">LOKASI</div>
                        <div class="fw-semibold mt-1">{{ $booking->slot->lokasi }}</div>
                    </div>
                    @if($booking->deskripsi)
                    <div class="col-12">
                        <div class="text-muted" style="font-size:11px;font-weight:600">DESKRIPSI</div>
                        <div class="mt-1" style="font-size:13px">{{ $booking->deskripsi }}</div>
                    </div>
                    @endif
                    @if($booking->alasan_batal)
                    <div class="col-12">
                        <div class="alert alert-danger py-2 mb-0" style="font-size:12px">
                            <i class="bi bi-info-circle"></i> <strong>Alasan Batal:</strong> {{ $booking->alasan_batal }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        @if($booking->note)
        <div class="card">
            <div class="card-header" style="background:#eff6ff"><i class="bi bi-journal-text"></i> <span style="color:var(--primary)">Catatan Dosen</span></div>
            <div class="card-body">
                <div class="notes-box mb-2">
                    <strong style="font-size:11px;color:var(--primary)"><i class="bi bi-chat-quote"></i> Hasil Konsultasi:</strong>
                    <p class="mb-0 mt-1">{{ $booking->note->catatan }}</p>
                </div>
                @if($booking->note->tindak_lanjut)
                <div class="notes-box tindak-lanjut mb-2">
                    <strong style="font-size:11px;color:var(--success)"><i class="bi bi-arrow-right-circle"></i> Tindak Lanjut:</strong>
                    <p class="mb-0 mt-1">{{ $booking->note->tindak_lanjut }}</p>
                </div>
                @endif
                @if($booking->note->jadwal_berikutnya)
                <div class="alert alert-info py-2 mb-0" style="font-size:12px">
                    <i class="bi bi-calendar-event"></i>
                    Konsultasi berikutnya: <strong>{{ \Carbon\Carbon::parse($booking->note->jadwal_berikutnya)->locale('id')->translatedFormat('l, d F Y') }}</strong>
                </div>
                @endif
            </div>
        </div>
        @elseif($booking->status === 'selesai' && auth()->user()->role === 'dosen')
        <div class="alert alert-warning d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            Konsultasi ini belum memiliki catatan.
            <a href="{{ route('notes.index') }}" class="btn btn-sm btn-warning ms-auto">Isi Catatan</a>
        </div>
        @endif
    </div>

    <div class="col-md-5">
        <div class="card">
            <div class="card-header"><i class="bi bi-gear-fill"></i> Aksi</div>
            <div class="card-body d-flex flex-column gap-2">
                @if($booking->status === 'menunggu' && auth()->user()->role === 'dosen')
                <form method="POST" action="{{ route('bookings.confirm', $booking) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-success w-100"><i class="bi bi-check-circle-fill"></i> Konfirmasi Booking</button>
                </form>
                @endif
                @if($booking->status === 'dikonfirmasi' && auth()->user()->role === 'dosen')
                <form method="POST" action="{{ route('bookings.complete', $booking) }}">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-primary w-100"><i class="bi bi-check-all"></i> Tandai Selesai</button>
                </form>
                @endif
                @if(in_array($booking->status,['menunggu','dikonfirmasi']))
                <button class="btn btn-outline-danger w-100" onclick="confirmCancel({{ $booking->id }})">
                    <i class="bi bi-x-circle"></i> Batalkan Booking
                </button>
                @endif
                <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-clock-history"></i> Timeline</div>
            <div class="card-body">
                <div style="font-size:12px;display:flex;flex-direction:column;gap:10px">
                    <div class="d-flex gap-2"><i class="bi bi-circle-fill text-primary" style="font-size:8px;margin-top:4px"></i><div><strong>Dibuat</strong><div class="text-muted">{{ $booking->created_at->locale('id')->translatedFormat('d M Y, H:i') }}</div></div></div>
                    @if($booking->confirmed_at)
                    <div class="d-flex gap-2"><i class="bi bi-circle-fill text-success" style="font-size:8px;margin-top:4px"></i><div><strong>Dikonfirmasi</strong><div class="text-muted">{{ $booking->confirmed_at->locale('id')->translatedFormat('d M Y, H:i') }}</div></div></div>
                    @endif
                    @if($booking->status === 'selesai')
                    <div class="d-flex gap-2"><i class="bi bi-circle-fill" style="font-size:8px;margin-top:4px;color:var(--success)"></i><div><strong>Selesai</strong><div class="text-muted">{{ $booking->updated_at->locale('id')->translatedFormat('d M Y, H:i') }}</div></div></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cancelModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title">Batalkan Booking</h5></div>
            <form method="POST" id="cancelForm">
                @csrf @method('PATCH')
                <div class="modal-body">
                    <label class="form-label">Alasan pembatalan <span class="text-danger">*</span></label>
                    <textarea name="alasan_batal" class="form-control" rows="3" required placeholder="Masukkan alasan pembatalan..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
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
