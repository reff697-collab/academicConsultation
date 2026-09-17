<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\WaitingList;

class StatsController extends Controller
{
    public function index()
    {
        $thisMonth = now()->month;
        $lastMonth = now()->subMonth()->month;

        $total     = Booking::whereMonth('created_at', $thisMonth)->count();
        $totalLast = Booking::whereMonth('created_at', $lastMonth)->count();
        $growth    = $totalLast > 0 ? round((($total - $totalLast) / $totalLast) * 100) : 0;

        $stats = [
            'total'          => $total,
            'growth'         => $growth,
            'mahasiswaAktif' => Booking::whereMonth('created_at', $thisMonth)->distinct('mahasiswa_id')->count('mahasiswa_id'),
            'totalMahasiswa' => User::where('role','mahasiswa')->count(),
            'kehadiran'      => $total > 0 ? round((Booking::where('status','selesai')->whereMonth('created_at', $thisMonth)->count() / $total) * 100) : 0,
            'batal'          => Booking::where('status','dibatalkan')->whereMonth('created_at', $thisMonth)->count(),
        ];

        // Monthly data (last 6 months)
        $monthlyData = ['labels'=>[], 'berhasil'=>[], 'batal'=>[]];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $monthlyData['labels'][]   = $m->locale('id')->translatedFormat('M');
            $monthlyData['berhasil'][] = Booking::where('status','selesai')->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count();
            $monthlyData['batal'][]    = Booking::where('status','dibatalkan')->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count();
        }

        // Attendance trend
        $attendanceData = ['labels'=>[], 'data'=>[]];
        for ($i = 5; $i >= 0; $i--) {
            $m   = now()->subMonths($i);
            $tot = Booking::whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count();
            $had = Booking::where('status','selesai')->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count();
            $attendanceData['labels'][] = $m->locale('id')->translatedFormat('M');
            $attendanceData['data'][]   = $tot > 0 ? round(($had/$tot)*100) : 0;
        }

        // Topic distribution
        $topikRaw      = Booking::selectRaw('topik, count(*) as c')->groupBy('topik')->pluck('c','topik');
        $topikDistData = ['labels' => $topikRaw->keys()->toArray(), 'data' => $topikRaw->values()->toArray()];

        // Success rate donut
        $successRateData = [
            'berhasil' => Booking::where('status','selesai')->count(),
            'batal'    => Booking::where('status','dibatalkan')->count(),
        ];

        // Top mahasiswa
        $topMahasiswa = User::where('role','mahasiswa')
            ->withCount(['bookingsMhs as booking_count'])
            ->orderByDesc('booking_count')->take(5)->get();

        // Dosen stats
        $dosenStats = User::where('role','dosen')
            ->withCount(['bookingsDosen as total_sesi'])
            ->withCount(['bookingsDosen as hadir' => fn($q) => $q->where('status','selesai')])
            ->withCount(['notesDosen as total_catatan'])
            ->orderByDesc('total_sesi')->get();

        return view('stats.index', compact(
            'stats','monthlyData','attendanceData','topikDistData',
            'successRateData','topMahasiswa','dosenStats'
        ));
    }
}
