{{-- HISTORY INDEX --}}
@extends('layouts.app')
@section('title','Riwayat Konsultasi')
@section('page-title','Riwayat Konsultasi')
@section('content')
<div class="page-header">
    <h4><i class="bi bi-clock-history text-primary"></i> Riwayat Konsultasi</h4>
    <p>Seluruh riwayat konsultasi lengkap dengan catatan dosen</p>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Total Riwayat</div><div class="stat-value text-primary">{{ $stats['total'] }}</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Selesai</div><div class="stat-value" style="color:var(--success)">{{ $stats['selesai'] }}</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Dibatalkan</div><div class="stat-value" style="color:var(--danger)">{{ $stats['batal'] }}</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Ada Catatan</div><div class="stat-value" style="color:var(--purple)">{{ $stats['ada_catatan'] }}</div></div></div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
            <select name="status" class="form-select form-select-sm" style="width:160px" onchange="this.form.submit()">
                <option value="">Semua Status</option>
                @foreach(['selesai','dibatalkan','tidak_hadir'] as $s)
                <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="topik" class="form-select form-select-sm" style="width:180px" onchange="this.form.submit()">
                <option value="">Semua Topik</option>
                @foreach(['Bimbingan Akademik','Tugas Akhir','Revisi Tugas','Konsultasi Nilai','Magang/KP','Lainnya'] as $t)
                <option value="{{ $t }}" {{ request('topik') === $t ? 'selected' : '' }}>{{ $t }}</option>
                @endforeach
            </select>
        </form>
    </div>
</div>

<div class="row g-3">
    @forelse($histories as $h)
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start gap-3">
                    <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary)">
                        {{ strtoupper(substr(auth()->user()->role === 'mahasiswa' ? $h->dosen->name : $h->mahasiswa->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <strong style="font-size:14px">{{ auth()->user()->role === 'mahasiswa' ? $h->dosen->name : $h->mahasiswa->name }}</strong>
                            <span class="badge-topik badge">{{ $h->topik }}</span>
                            <span class="badge badge-status-{{ $h->status }} ms-auto">{{ ucfirst(str_replace('_',' ',$h->status)) }}</span>
                        </div>
                        <div class="d-flex gap-3 text-muted mb-2" style="font-size:12px">
                            <span><i class="bi bi-calendar3"></i> {{ \Carbon\Carbon::parse($h->slot->tanggal)->locale('id')->translatedFormat('l, d F Y') }}</span>
                            <span><i class="bi bi-clock"></i> {{ substr($h->slot->jam_mulai,0,5) }} – {{ substr($h->slot->jam_selesai,0,5) }}</span>
                            <span><i class="bi bi-geo-alt"></i> {{ $h->slot->lokasi }}</span>
                        </div>
                        @if($h->note)
                        <div class="notes-box">
                            <strong style="font-size:11px;color:var(--primary)"><i class="bi bi-journal-text"></i> Catatan Dosen:</strong>
                            <p class="mb-0 mt-1">{{ $h->note->catatan }}</p>
                        </div>
                        @if($h->note->tindak_lanjut)
                        <div class="notes-box tindak-lanjut mt-1">
                            <strong style="font-size:11px;color:var(--success)"><i class="bi bi-arrow-right-circle"></i> Tindak Lanjut:</strong>
                            <p class="mb-0 mt-1">{{ $h->note->tindak_lanjut }}</p>
                            @if($h->note->jadwal_berikutnya)
                            <div class="mt-1" style="font-size:11px"><i class="bi bi-calendar-event"></i> Konsultasi berikutnya: <strong>{{ \Carbon\Carbon::parse($h->note->jadwal_berikutnya)->locale('id')->translatedFormat('d F Y') }}</strong></div>
                            @endif
                        </div>
                        @endif
                        @elseif($h->status === 'selesai')
                        <div class="text-muted" style="font-size:12px;font-style:italic"><i class="bi bi-dash-circle"></i> Catatan dosen belum diisi</div>
                        @elseif($h->status === 'dibatalkan' && $h->alasan_batal)
                        <div class="alert alert-danger py-2 mb-0" style="font-size:12px"><i class="bi bi-info-circle"></i> Alasan batal: {{ $h->alasan_batal }}</div>
                        @endif
                    </div>
                    <a href="{{ route('bookings.show', $h) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-clock-history"></i><p>Belum ada riwayat konsultasi</p></div></div></div>
    </div>
    @endforelse
</div>
@if($histories->hasPages())
<div class="mt-3">{{ $histories->withQueryString()->links() }}</div>
@endif
@endsection
