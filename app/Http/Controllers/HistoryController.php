<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

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
