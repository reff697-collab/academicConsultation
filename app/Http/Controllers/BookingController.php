<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ConsultationSlot;
use App\Models\User;
use App\Models\WaitingList;
use Carbon\Carbon;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $q = Booking::with(['dosen','mahasiswa','slot'])
            ->when($user->role === 'mahasiswa', fn($q) => $q->where('mahasiswa_id', $user->id))
            ->when($user->role === 'dosen', fn($q) => $q->where('dosen_id', $user->id))
            ->when($request->status, fn($q,$v) => $q->where('status',$v))
            ->when($request->topik,  fn($q,$v) => $q->where('topik',$v))
            ->when($request->search, fn($q,$v) => $q->where(function($q) use ($v) {
                $q->whereHas('mahasiswa', fn($q) => $q->where('name','like',"%$v%"))
                  ->orWhereHas('dosen',     fn($q) => $q->where('name','like',"%$v%"))
                  ->orWhere('kode_booking','like',"%$v%");
            }))
            ->latest();

        return view('bookings.index', ['bookings' => $q->paginate(15)]);
    }

    public function create()
    {
        $dosens = User::where('role','dosen')->orderBy('name')->get();
        return view('bookings.create', compact('dosens'));
    }

    public function getSlots($dosenId)
    {
        $user = auth()->user();

        $slots = ConsultationSlot::where('dosen_id', $dosenId)
            ->where('tanggal', '>=', now()->toDateString())
            ->where('status', 'tersedia')
            ->whereDoesntHave('bookings', fn($q) => $q->whereIn('status',['menunggu','dikonfirmasi']))
            ->orderBy('tanggal')->orderBy('jam_mulai')
            ->take(5)->get();

        $fullSlots = ConsultationSlot::where('dosen_id', $dosenId)
            ->where('tanggal', '>=', now()->toDateString())
            ->whereHas('bookings', fn($q) => $q->whereIn('status',['menunggu','dikonfirmasi']))
            ->orderBy('tanggal')->orderBy('jam_mulai')
            ->take(5)->get();

        $waitingCount = WaitingList::where('dosen_id', $dosenId)->where('status','menunggu')->count();
        $alreadyInQueue = WaitingList::where('dosen_id', $dosenId)
            ->where('mahasiswa_id', $user->id)
            ->where('status','menunggu')->exists();

        $formatted = $slots->map(fn($s) => [
            'id'          => $s->id,
            'hari'        => Carbon::parse($s->tanggal)->locale('id')->translatedFormat('l'),
            'tanggal_fmt' => Carbon::parse($s->tanggal)->locale('id')->translatedFormat('d M Y'),
            'jam_mulai'   => $s->jam_mulai,
            'jam_selesai' => $s->jam_selesai,
            'lokasi'      => $s->lokasi,
            'penuh'       => false,
        ]);

        $formattedFull = $fullSlots->map(fn($s) => [
            'id'          => $s->id,
            'hari'        => Carbon::parse($s->tanggal)->locale('id')->translatedFormat('l'),
            'tanggal_fmt' => Carbon::parse($s->tanggal)->locale('id')->translatedFormat('d M Y'),
            'jam_mulai'   => $s->jam_mulai,
            'jam_selesai' => $s->jam_selesai,
            'lokasi'      => $s->lokasi,
            'penuh'       => true,
        ]);

        return response()->json([
            'slots'            => $formatted,
            'full_slots'       => $formattedFull,
            'waiting_count'    => $waitingCount,
            'already_in_queue' => $alreadyInQueue,
            'dosen_id'         => $dosenId,
        ]);
    }

    public function checkConflict(Request $request)
    {
        $slot = ConsultationSlot::findOrFail($request->slot_id);
        $user = auth()->user();
        $hasConflict = false;
        $checks = [];

        $slotBooked = Booking::where('slot_id', $slot->id)->whereIn('status',['menunggu','dikonfirmasi'])->exists();
        if ($slotBooked) {
            $checks[] = ['ok' => false, 'message' => 'Slot ini sudah dipesan oleh mahasiswa lain'];
            $hasConflict = true;
        } else {
            $checks[] = ['ok' => true, 'message' => 'Slot tersedia dan belum dipesan'];
        }

        $mhsConflict = Booking::where('mahasiswa_id', $user->id)->whereIn('status',['menunggu','dikonfirmasi'])
            ->whereHas('slot', fn($q) => $q->where('tanggal', $slot->tanggal)
                ->where('jam_mulai', '<', $slot->jam_selesai)
                ->where('jam_selesai', '>', $slot->jam_mulai))->exists();
        if ($mhsConflict) {
            $checks[] = ['ok' => false, 'message' => 'Anda sudah memiliki booking lain di waktu yang sama'];
            $hasConflict = true;
        } else {
            $checks[] = ['ok' => true, 'message' => 'Tidak ada konflik jadwal dengan booking Anda yang lain'];
        }

        $dosenConflict = Booking::where('dosen_id', $slot->dosen_id)->whereIn('status',['menunggu','dikonfirmasi'])
            ->whereHas('slot', fn($q) => $q->where('tanggal', $slot->tanggal)
                ->where('jam_mulai', '<', $slot->jam_selesai)
                ->where('jam_selesai', '>', $slot->jam_mulai))->exists();
        if ($dosenConflict) {
            $checks[] = ['ok' => false, 'message' => 'Dosen sudah memiliki sesi konsultasi di waktu ini'];
            $hasConflict = true;
        } else {
            $checks[] = ['ok' => true, 'message' => 'Dosen tersedia di jadwal ini'];
        }

        return response()->json(['has_conflict' => $hasConflict, 'checks' => $checks]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'dosen_id'  => 'required|exists:users,id',
            'slot_id'   => 'required|exists:consultation_slots,id',
            'topik'     => 'required|in:Bimbingan Akademik,Tugas Akhir,Revisi Tugas,Konsultasi Nilai,Magang/KP,Lainnya',
            'deskripsi' => 'nullable|max:500',
        ]);

        $slot = ConsultationSlot::findOrFail($request->slot_id);
        $user = auth()->user();

        if (Booking::where('slot_id', $slot->id)->whereIn('status',['menunggu','dikonfirmasi'])->exists()) {
            return back()->with('error', 'Slot ini baru saja dipesan. Silakan pilih slot lain.')->withInput();
        }
        if (Booking::where('mahasiswa_id', $user->id)->whereIn('status',['menunggu','dikonfirmasi'])
            ->whereHas('slot', fn($q) => $q->where('tanggal',$slot->tanggal)
                ->where('jam_mulai','<',$slot->jam_selesai)
                ->where('jam_selesai','>',$slot->jam_mulai))->exists()) {
            return back()->with('error', 'Anda sudah memiliki booking lain di waktu yang sama!')->withInput();
        }

        $booking = Booking::create([
            'kode_booking' => 'BK' . strtoupper(uniqid()),
            'mahasiswa_id' => $user->id,
            'dosen_id'     => $request->dosen_id,
            'slot_id'      => $slot->id,
            'topik'        => $request->topik,
            'deskripsi'    => $request->deskripsi,
            'status'       => 'menunggu',
        ]);

        return redirect()->route('bookings.show', $booking)
            ->with('success', 'Booking berhasil dibuat! Kode: ' . $booking->kode_booking);
    }

    public function joinQueue(Request $request)
    {
        $request->validate([
            'dosen_id'  => 'required|exists:users,id',
            'slot_id'   => 'required|exists:consultation_slots,id',
            'topik'     => 'required|in:Bimbingan Akademik,Tugas Akhir,Revisi Tugas,Konsultasi Nilai,Magang/KP,Lainnya',
            'deskripsi' => 'nullable|max:500',
        ]);

        $user = auth()->user();

        $exists = WaitingList::where('dosen_id', $request->dosen_id)
            ->where('mahasiswa_id', $user->id)
            ->where('status','menunggu')->exists();

        if ($exists) {
            return back()->with('error', 'Anda sudah terdaftar di waiting list dosen ini!');
        }

        $posisi = WaitingList::where('dosen_id', $request->dosen_id)
            ->where('slot_id', $request->slot_id)
            ->where('status','menunggu')->count() + 1;

        WaitingList::create([
            'mahasiswa_id' => $user->id,
            'dosen_id'     => $request->dosen_id,
            'slot_id'      => $request->slot_id,
            'topik'        => $request->topik,
            'deskripsi'    => $request->deskripsi ?? '',
            'posisi'       => $posisi,
            'status'       => 'menunggu',
        ]);

        return redirect()->route('queue.index')
            ->with('success', 'Berhasil masuk waiting list! Posisi Anda ke-' . $posisi . '. Kami akan kabari jika ada slot kosong.');
    }

    public function show(Booking $booking)
    {
        $booking->load(['dosen','mahasiswa','slot','note']);
        return view('bookings.show', compact('booking'));
    }

    public function confirm(Booking $booking)
    {
        $booking->update(['status' => 'dikonfirmasi', 'confirmed_at' => now()]);
        return back()->with('success', 'Booking dikonfirmasi!');
    }

    public function complete(Booking $booking)
    {
        $booking->update(['status' => 'selesai']);
        return back()->with('success', 'Konsultasi ditandai selesai!');
    }

    public function cancel(Request $request, Booking $booking)
    {
        $request->validate(['alasan_batal' => 'required|max:200']);
        $booking->update(['status' => 'dibatalkan', 'alasan_batal' => $request->alasan_batal]);

        // Otomatis promosikan mahasiswa pertama di waiting list
        $next = WaitingList::where('slot_id', $booking->slot_id)
            ->where('status', 'menunggu')
            ->orderBy('posisi')
            ->first();

        if ($next) {
            // Buatkan booking baru otomatis untuk mahasiswa dari waiting list
            Booking::create([
                'kode_booking' => 'BK' . strtoupper(uniqid()),
                'mahasiswa_id' => $next->mahasiswa_id,
                'dosen_id'     => $next->dosen_id,
                'slot_id'      => $next->slot_id,
                'topik'        => $next->topik,
                'deskripsi'    => $next->deskripsi,
                'status'       => 'dikonfirmasi',
                'confirmed_at' => now(),
            ]);

            // Tandai waiting list sudah diproses
            $next->update(['status' => 'diproses']);

            // Geser posisi antrean yang tersisa
            WaitingList::where('slot_id', $booking->slot_id)
                ->where('status', 'menunggu')
                ->where('posisi', '>', $next->posisi)
                ->decrement('posisi');

            $namaMhs = $next->mahasiswa->name;
            return back()->with('success', "Booking berhasil dibatalkan. Slot otomatis diberikan ke {$namaMhs} dari waiting list! 🎉");
        }

        return back()->with('success', 'Booking berhasil dibatalkan.');
    }
}
