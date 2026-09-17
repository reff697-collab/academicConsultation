<?php
namespace App\Http\Controllers;

use App\Models\ConsultationSlot;
use Illuminate\Http\Request;

class SlotController extends Controller
{
    public function index()
    {
        $user  = auth()->user();
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
            'tanggal'     => 'required|date|after_or_equal:today',
            'jam_mulai'   => 'required',
            'jam_selesai' => 'required|after:jam_mulai',
            'lokasi'      => 'required|max:100',
        ]);

        ConsultationSlot::create([
            'dosen_id'    => auth()->id(),
            'tanggal'     => $request->tanggal,
            'jam_mulai'   => $request->jam_mulai,
            'jam_selesai' => $request->jam_selesai,
            'lokasi'      => $request->lokasi,
            'kapasitas'   => 1,
            'status'      => 'tersedia',
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
