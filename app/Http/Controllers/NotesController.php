<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ConsultationNote;
use Illuminate\Http\Request;

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
            'catatan'           => 'required|max:1000',
            'tindak_lanjut'     => 'nullable|max:500',
            'jadwal_berikutnya' => 'nullable|date|after:today',
            'status_lanjut'     => 'required|in:selesai,perlu_lanjut,ditunda',
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
