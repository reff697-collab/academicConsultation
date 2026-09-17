@extends('layouts.app')
@section('title','Statistik & Monitoring')
@section('page-title','Statistik & Monitoring')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-bar-chart-fill text-primary"></i> Dashboard Statistik & Monitoring</h4>
    <p>Visualisasi data konsultasi secara menyeluruh</p>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label"><i class="bi bi-calendar3"></i> Total Konsultasi</div><div class="stat-value text-primary">{{ $stats['total'] }}</div><div class="stat-delta text-success"><i class="bi bi-graph-up-arrow"></i> +{{ $stats['growth'] }}% dari bulan lalu</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label"><i class="bi bi-people"></i> Mahasiswa Aktif</div><div class="stat-value" style="color:var(--teal)">{{ $stats['mahasiswaAktif'] }}</div><div class="stat-delta text-muted">dari {{ $stats['totalMahasiswa'] }} terdaftar</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label"><i class="bi bi-star-fill"></i> Tingkat Kehadiran</div><div class="stat-value" style="color:var(--warning)">{{ $stats['kehadiran'] }}%</div><div class="stat-delta text-success">Target: 85%</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label"><i class="bi bi-x-circle"></i> Pembatalan</div><div class="stat-value" style="color:var(--danger)">{{ $stats['batal'] }}</div><div class="stat-delta text-muted">Total dibatalkan</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><i class="bi bi-bar-chart-fill"></i> Konsultasi per Bulan ({{ now()->year }})</div>
            <div class="card-body">
                <div class="chart-container" style="height:230px">
                    <canvas id="monthlyChart" role="img" aria-label="Grafik konsultasi per bulan"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-pie-chart-fill"></i> Booking vs Batal</div>
            <div class="card-body">
                <div class="chart-container" style="height:190px">
                    <canvas id="successRateChart" role="img" aria-label="Rasio booking berhasil dan dibatalkan"></canvas>
                </div>
                <div class="d-flex justify-content-center gap-3 mt-2">
                    <span style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;border-radius:2px;background:#2563eb;display:inline-block"></span>Berhasil</span>
                    <span style="font-size:11px;color:#64748b;display:flex;align-items:center;gap:4px"><span style="width:10px;height:10px;border-radius:2px;background:#dc2626;display:inline-block"></span>Dibatalkan</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-graph-up"></i> Tren Kehadiran (%)</div>
            <div class="card-body">
                <div class="chart-container" style="height:200px">
                    <canvas id="attendanceChart" role="img" aria-label="Tren kehadiran per bulan"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-tags-fill"></i> Distribusi Topik Konsultasi</div>
            <div class="card-body">
                <div class="chart-container" style="height:200px">
                    <canvas id="topikDistChart" role="img" aria-label="Distribusi topik konsultasi"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-trophy-fill"></i> Top Mahasiswa Paling Aktif</div>
            <div class="card-body">
                @foreach($topMahasiswa as $idx => $mhs)
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="fw-semibold text-muted" style="width:20px;font-size:12px">{{ $idx+1 }}.</span>
                    <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary);font-size:11px">
                        {{ strtoupper(substr($mhs->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div style="font-size:12px;font-weight:600">{{ $mhs->name }}</div>
                        <div class="progress mt-1">
                            <div class="progress-bar" style="width:{{ min(100, $mhs->booking_count * 11) }}%;background:var(--primary)"></div>
                        </div>
                    </div>
                    <span class="fw-semibold text-primary" style="font-size:12px">{{ $mhs->booking_count }}x</span>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-person-badge-fill"></i> Statistik Dosen</div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Dosen</th><th>Sesi</th><th>Kehadiran</th><th>Catatan</th></tr></thead>
                    <tbody>
                        @foreach($dosenStats as $ds)
                        <tr>
                            <td style="font-size:12px;font-weight:600">{{ Str::limit($ds->name, 20) }}</td>
                            <td style="font-size:12px">{{ $ds->total_sesi }}</td>
                            <td>
                                @php $pct = $ds->total_sesi > 0 ? round(($ds->hadir/$ds->total_sesi)*100) : 0; @endphp
                                <span class="badge {{ $pct >= 85 ? 'badge-status-selesai' : 'badge-status-menunggu' }}">{{ $pct }}%</span>
                            </td>
                            <td style="font-size:12px">{{ $ds->total_catatan }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const monthlyData = @json($monthlyData);
const attendanceData = @json($attendanceData);
const topikDist = @json($topikDistData);
const successRate = @json($successRateData);

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthlyData.labels,
        datasets: [
            { label: 'Berhasil', data: monthlyData.berhasil, backgroundColor: '#2563eb', borderRadius: 4 },
            { label: 'Dibatalkan', data: monthlyData.batal, backgroundColor: '#fca5a5', borderRadius: 4 }
        ]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, stacked: true, grid: { color: '#f1f5f9' } }, x: { stacked: true, grid: { display: false } } } }
});

new Chart(document.getElementById('successRateChart'), {
    type: 'doughnut',
    data: { labels: ['Berhasil', 'Dibatalkan'], datasets: [{ data: [successRate.berhasil, successRate.batal], backgroundColor: ['#2563eb', '#dc2626'], borderWidth: 0 }] },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, cutout: '60%' }
});

new Chart(document.getElementById('attendanceChart'), {
    type: 'line',
    data: {
        labels: attendanceData.labels,
        datasets: [{ label: 'Kehadiran %', data: attendanceData.data, borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.08)', fill: true, tension: .4, pointRadius: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { min: 0, max: 100, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('topikDistChart'), {
    type: 'bar',
    data: {
        labels: topikDist.labels,
        datasets: [{ data: topikDist.data, backgroundColor: ['#2563eb','#16a34a','#7c3aed','#0f766e','#d97706','#64748b'], borderRadius: 4 }]
    },
    options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#f1f5f9' } }, x: { grid: { display: false }, ticks: { font: { size: 10 } } } } }
});
</script>
@endpush
