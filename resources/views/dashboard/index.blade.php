@extends('layouts.app')
@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="page-header">
    <h4>Selamat datang, {{ auth()->user()->name }}! 👋</h4>
    <p>Berikut ringkasan aktivitas konsultasi {{ auth()->user()->role === 'mahasiswa' ? 'Anda' : 'sistem' }} hari ini.</p>
</div>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label"><i class="bi bi-calendar-check"></i> Total Konsultasi</div>
                    <div class="stat-value text-primary">{{ $stats['total'] }}</div>
                    <div class="stat-delta text-success"><i class="bi bi-graph-up-arrow"></i> Bulan ini</div>
                </div>
                <div class="stat-icon" style="background:var(--primary-light);color:var(--primary)"><i class="bi bi-calendar-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label"><i class="bi bi-check-circle"></i> Berhasil</div>
                    <div class="stat-value" style="color:var(--success)">{{ $stats['selesai'] }}</div>
                    <div class="stat-delta" style="color:var(--text-muted)">Tingkat: {{ $stats['total'] > 0 ? round(($stats['selesai']/$stats['total'])*100) : 0 }}%</div>
                </div>
                <div class="stat-icon" style="background:var(--success-bg);color:var(--success)"><i class="bi bi-patch-check-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label"><i class="bi bi-x-circle"></i> Pembatalan</div>
                    <div class="stat-value" style="color:var(--danger)">{{ $stats['batal'] }}</div>
                    <div class="stat-delta" style="color:var(--text-muted)">Total dibatalkan</div>
                </div>
                <div class="stat-icon" style="background:var(--danger-bg);color:var(--danger)"><i class="bi bi-x-circle-fill"></i></div>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card">
            <div class="d-flex align-items-start justify-content-between">
                <div>
                    <div class="stat-label"><i class="bi bi-clock"></i> Waiting List</div>
                    <div class="stat-value" style="color:var(--warning)">{{ $stats['waiting'] }}</div>
                    <div class="stat-delta" style="color:var(--warning)"><i class="bi bi-hourglass-split"></i> Menunggu slot</div>
                </div>
                <div class="stat-icon" style="background:var(--warning-bg);color:var(--warning)"><i class="bi bi-hourglass-split"></i></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- CHART KONSULTASI MINGGUAN -->
    <div class="col-md-8">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart-fill"></i> Konsultasi per Minggu ({{ now()->format('F Y') }})
            </div>
            <div class="card-body">
                <div class="chart-container" style="height:220px">
                    <canvas id="weeklyChart" role="img" aria-label="Grafik konsultasi per minggu bulan ini"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- CHART DISTRIBUSI TOPIK -->
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-pie-chart-fill"></i> Distribusi Topik
            </div>
            <div class="card-body">
                <div class="chart-container" style="height:180px">
                    <canvas id="topicChart" role="img" aria-label="Distribusi topik konsultasi"></canvas>
                </div>
                <div class="mt-3 d-flex flex-wrap gap-2" id="topicLegend"></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <!-- UPCOMING BOOKINGS -->
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-calendar-event-fill"></i>
                Konsultasi Mendatang
                <a href="{{ route('bookings.index') }}" class="btn btn-sm btn-outline-primary ms-auto">Lihat Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($upcomingBookings as $b)
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary)">
                        {{ strtoupper(substr(auth()->user()->role === 'mahasiswa' ? $b->dosen->name : $b->mahasiswa->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div class="fw-semibold" style="font-size:13px">
                            {{ auth()->user()->role === 'mahasiswa' ? $b->dosen->name : $b->mahasiswa->name }}
                        </div>
                        <div class="text-muted" style="font-size:11px">
                            <span class="badge-topik badge">{{ $b->topik }}</span>
                            · {{ \Carbon\Carbon::parse($b->slot->tanggal)->locale('id')->translatedFormat('d M Y') }}
                            · {{ substr($b->slot->jam_mulai,0,5) }}–{{ substr($b->slot->jam_selesai,0,5) }}
                        </div>
                    </div>
                    <span class="badge badge-status-{{ $b->status }}">{{ ucfirst(str_replace('_',' ',$b->status)) }}</span>
                </div>
                @empty
                <div class="empty-state py-4">
                    <i class="bi bi-calendar-x"></i>
                    <p>Tidak ada booking mendatang</p>
                    @if(auth()->user()->role === 'mahasiswa')
                    <a href="{{ route('bookings.create') }}" class="btn btn-sm btn-primary mt-2">
                        <i class="bi bi-plus"></i> Buat Booking
                    </a>
                    @endif
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- AKTIVITAS TERBARU & MAHASISWA AKTIF -->
    <div class="col-md-5">
        @if(auth()->user()->role !== 'mahasiswa')
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-trophy-fill"></i> Mahasiswa Paling Aktif</div>
            <div class="card-body">
                @foreach($topMahasiswa as $idx => $mhs)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary);font-size:11px">
                        {{ strtoupper(substr($mhs->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div style="font-size:12px;font-weight:600">{{ $mhs->name }}</div>
                        <div class="progress mt-1">
                            <div class="progress-bar" style="width:{{ min(100, $mhs->booking_count * 12) }}%;background:var(--primary)"></div>
                        </div>
                    </div>
                    <span class="fw-semibold" style="font-size:12px;color:var(--primary)">{{ $mhs->booking_count }}x</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><i class="bi bi-activity"></i> Aktivitas Terbaru</div>
            <div class="card-body p-0">
                @foreach($recentActivity as $a)
                <div class="d-flex align-items-start gap-2 px-4 py-2 border-bottom">
                    <span class="badge badge-status-{{ $a->status }} mt-1 flex-shrink-0">{{ ucfirst(str_replace('_',' ',$a->status)) }}</span>
                    <div>
                        <div style="font-size:12px">
                            {{ auth()->user()->role === 'mahasiswa' ? $a->dosen->name : $a->mahasiswa->name }}
                            — {{ $a->topik }}
                        </div>
                        <div class="text-muted" style="font-size:11px">{{ $a->created_at->diffForHumans() }}</div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const weeklyData = @json($weeklyData);
const topikData = @json($topikData);
const colors = ['#2563eb','#16a34a','#7c3aed','#0f766e','#d97706','#64748b'];

// Weekly bar chart
new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: weeklyData.labels,
        datasets: [{
            label: 'Konsultasi',
            data: weeklyData.data,
            backgroundColor: '#2563eb',
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            y: { beginAtZero: true, grid: { color: '#f1f5f9' }, ticks: { font: { size: 11 }, stepSize: 1 } },
            x: { grid: { display: false }, ticks: { font: { size: 11 } } }
        }
    }
});

// Topic donut chart
const topikChart = new Chart(document.getElementById('topicChart'), {
    type: 'doughnut',
    data: {
        labels: topikData.labels,
        datasets: [{ data: topikData.data, backgroundColor: colors, borderWidth: 0 }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '65%'
    }
});

// Custom legend
const legend = document.getElementById('topicLegend');
topikData.labels.forEach((label, i) => {
    legend.innerHTML += `<span style="display:flex;align-items:center;gap:4px;font-size:11px;color:#64748b">
        <span style="width:8px;height:8px;border-radius:2px;background:${colors[i]};display:inline-block"></span>${label}
    </span>`;
});
</script>
@endpush
