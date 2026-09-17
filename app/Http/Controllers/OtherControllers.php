<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ConsultationNote;
use App\Models\ConsultationSlot;
use App\Models\User;
use App\Models\WaitingList;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// ============================================================
// HistoryController
// ============================================================
class HistoryController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $histories = Booking::with(['dosen','mahasiswa','slot','note'])
            ->when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen',     fn($q) => $q->where('dosen_id', $user->id))
            ->whereIn('status', ['selesai','dibatalkan','tidak_hadir'])
            ->when($request->status, fn($q,$v) => $q->where('status',$v))
            ->when($request->topik,  fn($q,$v) => $q->where('topik',$v))
            ->latest()->paginate(10);

        $baseQ = Booking::when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen', fn($q) => $q->where('dosen_id', $user->id))
            ->whereIn('status', ['selesai','dibatalkan','tidak_hadir']);

        $stats = [
            'total'      => (clone $baseQ)->count(),
            'selesai'    => (clone $baseQ)->where('status','selesai')->count(),
            'batal'      => (clone $baseQ)->where('status','dibatalkan')->count(),
            'ada_catatan'=> (clone $baseQ)->has('note')->count(),
        ];

        return view('history.index', compact('histories','stats'));
    }
}

// ============================================================
// QueueController
// ============================================================
class QueueController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $queueItems = WaitingList::with(['mahasiswa','dosen','slot'])
            ->where('status','menunggu')
            ->when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen',     fn($q) => $q->where('dosen_id', $user->id))
            ->orderBy('posisi')->get();

        $queueByDosen = $queueItems->groupBy('dosen_id');
        $totalQueue = WaitingList::where('status','menunggu')->count();
        $myQueueCount = WaitingList::where($user->role === 'mahasiswa' ? 'mahasiswa_id' : 'dosen_id', $user->id)->where('status','menunggu')->count();
        $promoted = WaitingList::where('status','diproses')->count();

        return view('queue.index', compact('queueByDosen','totalQueue','myQueueCount','promoted'));
    }

    public function leave(WaitingList $waitingList)
    {
        $waitingList->update(['status' => 'batal']);
        return back()->with('success', 'Berhasil keluar dari waiting list.');
    }

    public function promote(WaitingList $waitingList)
    {
        $waitingList->update(['status' => 'diproses']);
        return back()->with('success', 'Mahasiswa berhasil dipromosikan ke slot konsultasi.');
    }
}

// ============================================================
// NotesController
// ============================================================
class NotesController extends Controller
{
    public function index()
    {
        $dosenId = auth()->id();
        $pendingNotes = Booking::with(['mahasiswa','slot'])
            ->where('dosen_id', $dosenId)
            ->where('status','selesai')
            ->doesntHave('note')
            ->latest()->get();

        $recentNotes = ConsultationNote::with(['booking.mahasiswa'])
            ->where('dosen_id', $dosenId)
            ->latest()->take(5)->get();

        return view('notes.index', compact('pendingNotes','recentNotes'));
    }

    public function store(Request $request, $bookingId)
    {
        $request->validate([
            'catatan'          => 'required|max:1000',
            'tindak_lanjut'    => 'nullable|max:500',
            'jadwal_berikutnya'=> 'nullable|date|after:today',
            'status_lanjut'    => 'required|in:selesai,perlu_lanjut,ditunda',
        ]);

        ConsultationNote::updateOrCreate(
            ['booking_id' => $bookingId],
            [
                'dosen_id'          => auth()->id(),
                'catatan'           => $request->catatan,
                'tindak_lanjut'     => $request->tindak_lanjut,
                'jadwal_berikutnya' => $request->jadwal_berikutnya,
                'status_lanjut'     => $request->status_lanjut,
            ]
        );

        return back()->with('success', 'Catatan berhasil disimpan dan dikirim ke mahasiswa.');
    }
}

// ============================================================
// StatsController
// ============================================================
class StatsController extends Controller
{
    public function index()
    {
        $thisMonth = now()->month;
        $lastMonth = now()->subMonth()->month;

        $total = Booking::whereMonth('created_at', $thisMonth)->count();
        $totalLast = Booking::whereMonth('created_at', $lastMonth)->count();
        $growth = $totalLast > 0 ? round((($total - $totalLast) / $totalLast) * 100) : 0;

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

        // Attendance data
        $attendanceData = ['labels'=>[], 'data'=>[]];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonths($i);
            $tot = Booking::whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count();
            $had = Booking::where('status','selesai')->whereYear('created_at',$m->year)->whereMonth('created_at',$m->month)->count();
            $attendanceData['labels'][] = $m->locale('id')->translatedFormat('M');
            $attendanceData['data'][]   = $tot > 0 ? round(($had/$tot)*100) : 0;
        }

        // Topic distribution
        $topikRaw = Booking::selectRaw('topik, count(*) as c')->groupBy('topik')->pluck('c','topik');
        $topikDistData = ['labels'=>$topikRaw->keys()->toArray(), 'data'=>$topikRaw->values()->toArray()];

        // Success rate
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

        return view('stats.index', compact('stats','monthlyData','attendanceData','topikDistData','successRateData','topMahasiswa','dosenStats'));
    }
}

// ============================================================
// SlotController
// ============================================================
class SlotController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $slots = ConsultationSlot::with(['bookings'])
            ->when($user->role === 'dosen', fn($q) => $q->where('dosen_id', $user->id))
            ->where('tanggal', '>=', now()->toDateString())
            ->orderBy('tanggal')->orderBy('jam_mulai')
            ->paginate(20);
        return view('slots.index', compact('slots'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal'    => 'required|date|after_or_equal:today',
            'jam_mulai'  => 'required',
            'jam_selesai'=> 'required|after:jam_mulai',
            'lokasi'     => 'required|max:100',
        ]);

        ConsultationSlot::create([
            'dosen_id'   => auth()->id(),
            'tanggal'    => $request->tanggal,
            'jam_mulai'  => $request->jam_mulai,
            'jam_selesai'=> $request->jam_selesai,
            'lokasi'     => $request->lokasi,
            'kapasitas'  => 1,
            'status'     => 'tersedia',
        ]);

        return back()->with('success', 'Slot jadwal berhasil ditambahkan!');
    }

    public function destroy(ConsultationSlot $slot)
    {
        if ($slot->bookings()->whereIn('status',['menunggu','dikonfirmasi'])->exists()) {
            return back()->with('error', 'Slot tidak bisa dihapus karena masih ada booking aktif.');
        }
        $slot->delete();
        return back()->with('success', 'Slot berhasil dihapus.');
    }
}
