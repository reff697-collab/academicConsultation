<?php
namespace App\Http\Controllers;

use App\Models\WaitingList;

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
        $totalQueue   = WaitingList::where('status','menunggu')->count();
        $myQueueCount = WaitingList::where(
            $user->role === 'mahasiswa' ? 'mahasiswa_id' : 'dosen_id', $user->id
        )->where('status','menunggu')->count();
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
