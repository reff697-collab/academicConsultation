<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\User;
use App\Models\WaitingList;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $q = Booking::query();
        if ($user->role === 'mahasiswa') $q->where('mahasiswa_id', $user->id);
        elseif ($user->role === 'dosen') $q->where('dosen_id', $user->id);

        $stats = [
            'total'   => (clone $q)->whereMonth('created_at', now()->month)->count(),
            'selesai' => (clone $q)->where('status','selesai')->whereMonth('created_at', now()->month)->count(),
            'batal'   => (clone $q)->where('status','dibatalkan')->whereMonth('created_at', now()->month)->count(),
            'waiting' => WaitingList::where($user->role === 'mahasiswa' ? 'mahasiswa_id' : 'dosen_id', $user->id)->where('status','menunggu')->count(),
        ];

        // Upcoming bookings
        $upcomingBookings = Booking::with(['dosen','mahasiswa','slot'])
            ->join('consultation_slots', 'bookings.slot_id', '=', 'consultation_slots.id')
            ->select('bookings.*')
            ->when($user->role === 'mahasiswa', fn($q) => $q->where('bookings.mahasiswa_id', $user->id))
            ->when($user->role === 'dosen', fn($q) => $q->where('bookings.dosen_id', $user->id))
            ->whereIn('bookings.status', ['menunggu','dikonfirmasi'])
            ->where('consultation_slots.tanggal', '>=', now()->toDateString())
            ->orderBy('consultation_slots.tanggal')
            ->take(5)->get();

        // Weekly chart data
        $weeklyData = ['labels' => ['Minggu 1','Minggu 2','Minggu 3','Minggu 4'], 'data' => [0,0,0,0]];
        $bookingsMonth = Booking::when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen', fn($q) => $q->where('dosen_id', $user->id))
            ->whereMonth('created_at', now()->month)->get();
        foreach ($bookingsMonth as $b) {
            $weekNum = (int) ceil($b->created_at->day / 7) - 1;
            if ($weekNum >= 0 && $weekNum < 4) $weeklyData['data'][$weekNum]++;
        }

        // Topic distribution
        $topikRaw = Booking::when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen', fn($q) => $q->where('dosen_id', $user->id))
            ->selectRaw('topik, count(*) as c')->groupBy('topik')->pluck('c','topik');
        $topikData = ['labels' => $topikRaw->keys()->toArray(), 'data' => $topikRaw->values()->toArray()];

        // Top mahasiswa
        $topMahasiswa = User::where('role','mahasiswa')
            ->withCount(['bookingsMhs as booking_count' => fn($q) => $q->whereMonth('created_at', now()->month)])
            ->orderByDesc('booking_count')->take(5)->get();

        // Recent activity
        $recentActivity = Booking::with(['dosen','mahasiswa'])
            ->when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen', fn($q) => $q->where('dosen_id', $user->id))
            ->latest()->take(5)->get();

        return view('dashboard.index', compact('stats','upcomingBookings','weeklyData','topikData','topMahasiswa','recentActivity'));
    }
}
