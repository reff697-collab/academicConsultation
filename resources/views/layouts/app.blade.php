<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'EduConsult') — Sistem Konsultasi Akademik</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<div class="app-wrapper">
    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="bi bi-mortarboard-fill"></i></div>
            <div>
                <div class="brand-name">EduConsult</div>
                <div class="brand-sub">Sistem Konsultasi Akademik</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section-label">MENU UTAMA</div>
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span>Dashboard</span>
            </a>

            @if(auth()->user()->role === 'mahasiswa')
            <a href="{{ route('bookings.create') }}" class="nav-link {{ request()->routeIs('bookings.create') ? 'active' : '' }}">
                <i class="bi bi-calendar-plus-fill"></i><span>Booking Baru</span>
            </a>
            @endif

            <a href="{{ route('bookings.index') }}" class="nav-link {{ request()->routeIs('bookings.*') && !request()->routeIs('bookings.create') ? 'active' : '' }}">
                <i class="bi bi-calendar-check-fill"></i><span>
                @if(auth()->user()->role === 'mahasiswa') Booking Saya
                @else Semua Booking
                @endif
                </span>
            </a>

            <a href="{{ route('history.index') }}" class="nav-link {{ request()->routeIs('history.*') ? 'active' : '' }}">
                <i class="bi bi-clock-history"></i><span>Riwayat Konsultasi</span>
            </a>

            <div class="nav-section-label mt-3">FITUR KHUSUS</div>

            <a href="{{ route('queue.index') }}" class="nav-link {{ request()->routeIs('queue.*') ? 'active' : '' }}">
                <i class="bi bi-list-ol"></i><span>Antrean / Waiting List</span>
                @php $qCount = \App\Models\WaitingList::where(auth()->user()->role === 'mahasiswa' ? 'mahasiswa_id' : 'dosen_id', auth()->id())->where('status','menunggu')->count(); @endphp
                @if($qCount > 0)
                <span class="nav-badge">{{ $qCount }}</span>
                @endif
            </a>

            @if(auth()->user()->role === 'dosen' || auth()->user()->role === 'admin')
            <a href="{{ route('notes.index') }}" class="nav-link {{ request()->routeIs('notes.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i><span>Catatan Konsultasi</span>
            </a>
            @endif

            <a href="{{ route('stats.index') }}" class="nav-link {{ request()->routeIs('stats.*') ? 'active' : '' }}">
                <i class="bi bi-bar-chart-fill"></i><span>Statistik & Monitoring</span>
            </a>

            @if(auth()->user()->role === 'dosen' || auth()->user()->role === 'admin')
            <a href="{{ route('slots.index') }}" class="nav-link {{ request()->routeIs('slots.*') ? 'active' : '' }}">
                <i class="bi bi-clock-fill"></i><span>Kelola Jadwal Slot</span>
            </a>
            @endif
        </nav>

        <div class="sidebar-user">
            <div class="user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
            <div class="user-info">
                <div class="user-name">{{ Str::limit(auth()->user()->name, 18) }}</div>
                <div class="user-role">{{ ucfirst(auth()->user()->role) }} · {{ auth()->user()->nim_nidn }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-logout" title="Logout"><i class="bi bi-box-arrow-right"></i></button>
            </form>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <main class="main-content">
        <!-- TOPBAR -->
        <div class="topbar">
            <button class="btn-toggle-sidebar" onclick="toggleSidebar()">
                <i class="bi bi-list"></i>
            </button>
            <div class="topbar-title">@yield('page-title', 'Dashboard')</div>
            <div class="topbar-right">
                <div class="topbar-date">
                    <i class="bi bi-calendar3"></i>
                    {{ now()->locale('id')->translatedFormat('l, d F Y') }}
                </div>
                @php
                    $pendingCount = auth()->user()->role === 'mahasiswa'
                        ? \App\Models\Booking::where('mahasiswa_id', auth()->id())->where('status','menunggu')->count()
                        : \App\Models\Booking::where('dosen_id', auth()->id())->where('status','menunggu')->count();
                @endphp
                @if($pendingCount > 0)
                <div class="topbar-notif">
                    <i class="bi bi-bell-fill"></i>
                    <span class="notif-dot">{{ $pendingCount }}</span>
                </div>
                @endif
            </div>
        </div>

        <!-- PAGE CONTENT -->
        <div class="page-body">
            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-check-circle-fill"></i>
                {{ session('success') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            @endif
            @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2" role="alert">
                <i class="bi bi-exclamation-circle-fill"></i>
                {{ session('error') }}
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
            @endif

            @yield('content')
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('collapsed');
    document.querySelector('.main-content').classList.toggle('expanded');
}
$.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });
</script>
@stack('scripts')
</body>
</html>
