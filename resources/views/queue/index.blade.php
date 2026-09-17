@extends('layouts.app')
@section('title','Antrean Konsultasi')
@section('page-title','Antrean / Waiting List')
@section('content')
<div class="page-header">
    <h4><i class="bi bi-list-ol text-primary"></i> Consultation Queue — Waiting List</h4>
    <p>Daftar antrean konsultasi aktif per dosen</p>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-4 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Total Antrean</div><div class="stat-value" style="color:var(--warning)">{{ $totalQueue }}</div></div></div>
    <div class="col-md-4 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Saya di Antrean</div><div class="stat-value text-primary">{{ $myQueueCount }}</div></div></div>
    <div class="col-md-4 col-6"><div class="stat-card text-center"><div class="stat-label justify-content-center">Berhasil Dapat Slot</div><div class="stat-value" style="color:var(--success)">{{ $promoted }}</div></div></div>
</div>

@foreach($queueByDosen as $dosenId => $items)
@php $dosen = $items->first()->dosen; @endphp
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-person-fill"></i>
        {{ $dosen->name }}
        <span class="badge {{ $items->count() >= 3 ? 'badge-status-dibatalkan' : 'badge-status-menunggu' }} ms-2">
            {{ $items->count() }} dalam antrean
        </span>
        <span class="text-muted ms-auto" style="font-size:11px">
            {{ $items->count() >= 3 ? '🔴 Semua slot penuh' : '🟡 Slot hampir penuh' }}
        </span>
    </div>
    <div class="card-body">
        @foreach($items as $item)
        <div class="queue-item">
            <div class="queue-number">{{ $item->posisi }}</div>
            <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary);font-size:11px;width:30px;height:30px">
                {{ strtoupper(substr($item->mahasiswa->name, 0, 2)) }}
            </div>
            <div class="flex-1">
                <div style="font-size:13px;font-weight:600">{{ $item->mahasiswa->name }}</div>
                <div class="text-muted" style="font-size:11px">
                    <span class="badge-topik badge">{{ $item->topik }}</span>
                    · Masuk {{ $item->created_at->diffForHumans() }}
                </div>
            </div>
            @if(auth()->id() === $item->mahasiswa_id)
            <form method="POST" action="{{ route('queue.leave', $item) }}">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Yakin keluar dari antrean?')">
                    <i class="bi bi-x-lg"></i> Keluar
                </button>
            </form>
            @endif
            @if(auth()->user()->role === 'dosen' && auth()->id() === $dosenId)
            <form method="POST" action="{{ route('queue.promote', $item) }}">
                @csrf @method('PATCH')
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-check-lg"></i> Beri Slot
                </button>
            </form>
            @endif
        </div>
        @endforeach
    </div>
</div>
@endforeach

@if($queueByDosen->isEmpty())
<div class="card"><div class="card-body"><div class="empty-state"><i class="bi bi-list-check"></i><p>Tidak ada antrean aktif saat ini</p></div></div></div>
@endif
@endsection
