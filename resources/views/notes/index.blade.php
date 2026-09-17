@extends('layouts.app')
@section('title','Catatan Konsultasi')
@section('page-title','Catatan Konsultasi')
@section('content')
<div class="page-header">
    <h4><i class="bi bi-journal-text text-primary"></i> Automatic Consultation Notes</h4>
    <p>Isi catatan hasil konsultasi yang akan otomatis terkirim ke mahasiswa</p>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-pencil-fill"></i> Booking Selesai — Belum Ada Catatan</div>
            <div class="card-body p-0">
                @forelse($pendingNotes as $b)
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    <div class="avatar-circle" style="background:var(--primary-light);color:var(--primary);font-size:11px">
                        {{ strtoupper(substr($b->mahasiswa->name, 0, 2)) }}
                    </div>
                    <div class="flex-1">
                        <div style="font-size:13px;font-weight:600">{{ $b->mahasiswa->name }}</div>
                        <div class="text-muted" style="font-size:11px">
                            <span class="badge-topik badge">{{ $b->topik }}</span>
                            · {{ \Carbon\Carbon::parse($b->slot->tanggal)->locale('id')->translatedFormat('d M Y') }}
                        </div>
                    </div>
                    <button class="btn btn-sm btn-primary" onclick="openNoteForm({{ $b->id }}, '{{ addslashes($b->mahasiswa->name) }}', '{{ $b->topik }}')">
                        <i class="bi bi-pencil-square"></i> Isi Catatan
                    </button>
                </div>
                @empty
                <div class="empty-state"><i class="bi bi-check-all"></i><p>Semua konsultasi sudah ada catatannya</p></div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card" id="noteFormCard" style="display:none">
            <div class="card-header"><i class="bi bi-journal-plus"></i> Tambah Catatan untuk <span id="noteFor"></span></div>
            <div class="card-body">
                <form method="POST" id="noteForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Status Konsultasi</label>
                        <select name="status_lanjut" class="form-select">
                            <option value="selesai">Selesai</option>
                            <option value="perlu_lanjut">Perlu Tindak Lanjut</option>
                            <option value="ditunda">Ditunda</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Hasil & Catatan Konsultasi <span class="text-danger">*</span></label>
                        <textarea name="catatan" class="form-control" rows="4" required
                            placeholder="Tuliskan hasil konsultasi, poin penting yang dibahas..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tindak Lanjut yang Disarankan</label>
                        <textarea name="tindak_lanjut" class="form-control" rows="2"
                            placeholder="Apa yang harus dilakukan mahasiswa setelah ini?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jadwal Konsultasi Berikutnya (Opsional)</label>
                        <input type="date" name="jadwal_berikutnya" class="form-control" min="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary flex-1">
                            <i class="bi bi-send-fill"></i> Simpan & Kirim ke Mahasiswa
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="document.getElementById('noteFormCard').style.display='none'">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header"><i class="bi bi-clock-history"></i> Catatan Terbaru</div>
            <div class="card-body p-0">
                @foreach($recentNotes as $n)
                <div class="px-4 py-3 border-bottom">
                    <div class="d-flex align-items-center gap-2 mb-1">
                        <strong style="font-size:13px">{{ $n->booking->mahasiswa->name }}</strong>
                        <span class="badge-topik badge">{{ $n->booking->topik }}</span>
                        <span class="text-muted ms-auto" style="font-size:11px">{{ $n->created_at->diffForHumans() }}</span>
                    </div>
                    <div class="notes-box" style="margin-top:0">{{ Str::limit($n->catatan, 100) }}</div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function openNoteForm(id, name, topik) {
    document.getElementById('noteFor').textContent = name + ' — ' + topik;
    document.getElementById('noteForm').action = `/notes/${id}`;
    document.getElementById('noteFormCard').style.display = 'block';
    document.getElementById('noteFormCard').scrollIntoView({ behavior: 'smooth' });
}
</script>
@endpush
