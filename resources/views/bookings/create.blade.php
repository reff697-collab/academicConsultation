@extends('layouts.app')
@section('title', 'Booking Baru')
@section('page-title', 'Booking Konsultasi Baru')

@section('content')
<div class="page-header">
    <h4><i class="bi bi-calendar-plus-fill text-primary"></i> Booking Konsultasi Baru</h4>
    <p>Pilih dosen dan topik — sistem akan merekomendasikan jadwal terbaik secara otomatis.</p>
</div>

{{-- Form Booking Normal --}}
<form method="POST" action="{{ route('bookings.store') }}" id="bookingForm">
@csrf
<input type="hidden" name="slot_id" id="selectedSlot" value="{{ old('slot_id') }}">
<input type="hidden" name="topik" id="topikInput" value="{{ old('topik') }}">

<div class="row g-3">
    <div class="col-md-7">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person-fill"></i> Pilih Dosen</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Dosen Pembimbing / Pengampu</label>
                    <select name="dosen_id" id="dosenSelect" class="form-select @error('dosen_id') is-invalid @enderror" required>
                        <option value="">-- Pilih Dosen --</option>
                        @foreach($dosens as $d)
                        <option value="{{ $d->id }}" {{ old('dosen_id') == $d->id ? 'selected' : '' }}>
                            {{ $d->name }} ({{ $d->jurusan }})
                        </option>
                        @endforeach
                    </select>
                    @error('dosen_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-tags-fill"></i> Topik Konsultasi</div>
            <div class="card-body">
                <label class="form-label">Pilih kategori topik <span class="text-danger">*</span></label>
                <div class="topic-chips mb-3">
                    @php $topiks = ['Bimbingan Akademik','Tugas Akhir','Revisi Tugas','Konsultasi Nilai','Magang/KP','Lainnya'];
                    $icons = ['bi-book','bi-file-earmark-text','bi-pencil-square','bi-clipboard-data','bi-briefcase','bi-three-dots']; @endphp
                    @foreach($topiks as $i => $t)
                    <span class="topic-chip {{ old('topik') === $t ? 'active' : '' }}" data-value="{{ $t }}" onclick="selectTopic(this)">
                        <i class="bi {{ $icons[$i] }}"></i> {{ $t }}
                    </span>
                    @endforeach
                </div>
                @error('topik')<div class="text-danger" style="font-size:12px">{{ $message }}</div>@enderror

                <label class="form-label mt-2">Deskripsi Singkat</label>
                <textarea name="deskripsi" id="deskripsiInput" class="form-control" rows="3"
                    placeholder="Jelaskan hal yang ingin dikonsultasikan...">{{ old('deskripsi') }}</textarea>
            </div>
        </div>

        {{-- Conflict Alert --}}
        <div id="conflictZone" style="display:none">
            <div class="card mb-3" style="border-color:#fecaca">
                <div class="card-header" style="background:#fff4f4;border-color:#fecaca">
                    <i class="bi bi-shield-fill-check text-danger"></i>
                    <span style="color:#dc2626">Smart Conflict Alert</span>
                </div>
                <div class="card-body" id="conflictItems"></div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100" id="submitBtn" disabled>
            <i class="bi bi-calendar-check-fill"></i> Konfirmasi Booking
        </button>
    </div>

    <div class="col-md-5">
        {{-- Smart Recommendation --}}
        <div class="card mb-3">
            <div class="card-header" style="background:linear-gradient(135deg,#eff6ff,#f5f3ff)">
                <i class="bi bi-stars" style="color:#7c3aed"></i>
                <span style="color:#7c3aed">Smart Time Recommendation</span>
            </div>
            <div class="card-body" id="recommendationBox">
                <div class="empty-state" style="padding:24px 0">
                    <i class="bi bi-search"></i>
                    <p>Pilih dosen untuk melihat rekomendasi jadwal</p>
                </div>
            </div>
        </div>

        {{-- Waiting List Box --}}
        <div class="card" id="waitingListBox" style="display:none">
            <div class="card-header" style="background:#fffbeb;border-color:#fde68a">
                <i class="bi bi-hourglass-split" style="color:#d97706"></i>
                <span style="color:#d97706">Waiting List Tersedia</span>
            </div>
            <div class="card-body">
                <div class="alert alert-warning d-flex gap-2 mb-3" style="font-size:12px">
                    <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
                    <span>Semua slot <strong id="wlDosenName"></strong> sedang penuh. Masuk waiting list untuk dapat notifikasi saat ada slot kosong.</span>
                </div>
                <div id="wlAlreadyInfo" style="display:none">
                    <div class="alert alert-info" style="font-size:12px">
                        <i class="bi bi-info-circle"></i> Anda sudah terdaftar di waiting list dosen ini.
                        <a href="{{ route('queue.index') }}" class="fw-semibold">Lihat status antrean →</a>
                    </div>
                </div>
                <div id="wlFullSlots" class="mb-3"></div>
                <div id="wlFormArea"></div>
            </div>
        </div>
    </div>
</div>
</form>

{{-- Form Waiting List (terpisah dari form booking) --}}
<form method="POST" action="{{ route('bookings.queue') }}" id="queueForm" style="display:none">
@csrf
<input type="hidden" name="dosen_id" id="wlDosenId">
<input type="hidden" name="slot_id" id="wlSlotId">
<input type="hidden" name="topik" id="wlTopik">
<input type="hidden" name="deskripsi" id="wlDeskripsi">
</form>

@endsection

@push('scripts')
<script>
const dosenNames = {
    @foreach($dosens as $d)
    {{ $d->id }}: "{{ $d->name }}",
    @endforeach
};

function selectTopic(el) {
    document.querySelectorAll('.topic-chip').forEach(c => c.classList.remove('active'));
    el.classList.add('active');
    document.getElementById('topikInput').value = el.dataset.value;
    document.getElementById('wlTopik').value = el.dataset.value;
    checkSubmitReady();
}

function checkSubmitReady() {
    const slot = document.getElementById('selectedSlot').value;
    const topik = document.getElementById('topikInput').value;
    document.getElementById('submitBtn').disabled = !(slot && topik);
}

document.getElementById('dosenSelect').addEventListener('change', function() {
    const dosenId = this.value;
    const recBox = document.getElementById('recommendationBox');
    const wlBox = document.getElementById('waitingListBox');

    // Reset
    document.getElementById('selectedSlot').value = '';
    document.getElementById('conflictZone').style.display = 'none';
    document.getElementById('submitBtn').disabled = true;
    document.getElementById('submitBtn').className = 'btn btn-primary w-100';
    document.getElementById('submitBtn').innerHTML = '<i class="bi bi-calendar-check-fill"></i> Konfirmasi Booking';
    wlBox.style.display = 'none';
    checkSubmitReady();

    if (!dosenId) {
        recBox.innerHTML = `<div class="empty-state" style="padding:24px 0"><i class="bi bi-search"></i><p>Pilih dosen untuk melihat rekomendasi</p></div>`;
        return;
    }

    recBox.innerHTML = `<div class="text-center py-3 text-muted" style="font-size:13px"><div class="spinner-border spinner-border-sm me-2"></div>Memuat rekomendasi...</div>`;

    fetch(`/bookings/slots/${dosenId}`)
        .then(r => r.json())
        .then(data => {
            renderRecommendations(data);
        });
});

function renderRecommendations(data) {
    const recBox = document.getElementById('recommendationBox');
    const wlBox  = document.getElementById('waitingListBox');

    // Tampilkan slot tersedia
    if (data.slots && data.slots.length > 0) {
        const labels  = ['Paling Optimal','Slot Tercepat','Paling Fleksibel'];
        const badges  = [
            '<span class="badge" style="background:#dcfce7;color:#16a34a"><i class="bi bi-stars"></i> Terbaik</span>',
            '<span class="badge badge-status-dikonfirmasi"><i class="bi bi-lightning-fill"></i> Tercepat</span>',
            '<span class="badge badge-role-admin"><i class="bi bi-calendar-week"></i> Fleksibel</span>'
        ];
        let html = '';
        data.slots.forEach((slot, i) => {
            html += `<div class="rec-slot ${i===0?'best':''}" onclick="selectSlot(this,'${slot.id}')">
                <div>
                    <div class="rec-slot-time">${slot.hari}, ${slot.tanggal_fmt}</div>
                    <div class="rec-slot-label">${slot.jam_mulai.slice(0,5)} – ${slot.jam_selesai.slice(0,5)} · ${slot.lokasi}</div>
                    <div class="rec-slot-label text-muted" style="font-size:11px">${labels[i]||'Tersedia'}</div>
                </div>
                <div class="ms-auto">${badges[i]||''}</div>
            </div>`;
        });
        recBox.innerHTML = html;
    } else {
        recBox.innerHTML = `<div class="alert alert-warning mb-0" style="font-size:12px">
            <i class="bi bi-calendar-x me-1"></i>Tidak ada slot tersedia minggu ini untuk dosen ini.
        </div>`;
    }

    // Tampilkan waiting list jika ada slot penuh
    if (data.full_slots && data.full_slots.length > 0) {
        wlBox.style.display = 'block';
        document.getElementById('wlDosenName').textContent = dosenNames[data.dosen_id] || 'dosen ini';
        document.getElementById('wlDosenId').value = data.dosen_id;

        // Tampilkan slot penuh
        let fullHtml = '<div style="font-size:12px;font-weight:600;color:#92400e;margin-bottom:6px"><i class="bi bi-clock-fill"></i> Slot Penuh:</div>';
        data.full_slots.forEach(slot => {
            fullHtml += `<div class="rec-slot" style="border-color:#fde68a;background:#fffbeb;cursor:pointer" onclick="selectWaitingSlot('${slot.id}','${slot.hari} ${slot.tanggal_fmt}','${slot.jam_mulai.slice(0,5)}–${slot.jam_selesai.slice(0,5)}')">
                <div>
                    <div style="font-size:13px;font-weight:600;color:#92400e">${slot.hari}, ${slot.tanggal_fmt}</div>
                    <div style="font-size:11px;color:#b45309">${slot.jam_mulai.slice(0,5)} – ${slot.jam_selesai.slice(0,5)} · ${slot.lokasi}</div>
                </div>
                <span class="badge badge-status-menunggu ms-auto">Penuh</span>
            </div>`;
        });
        document.getElementById('wlFullSlots').innerHTML = fullHtml;

        if (data.already_in_queue) {
            document.getElementById('wlAlreadyInfo').style.display = 'block';
            document.getElementById('wlFormArea').innerHTML = '';
        } else {
            document.getElementById('wlAlreadyInfo').style.display = 'none';
            document.getElementById('wlFormArea').innerHTML = `
                <div id="wlSelected" class="alert alert-secondary" style="font-size:12px;display:none">
                    <i class="bi bi-check-circle"></i> Slot dipilih: <strong id="wlSelectedLabel"></strong>
                </div>
                <button type="button" class="btn btn-warning w-100" id="wlBtn" onclick="submitQueue()" disabled>
                    <i class="bi bi-hourglass-split"></i> Masuk Waiting List
                </button>
                <div class="text-muted mt-2" style="font-size:11px text-center">
                    <i class="bi bi-bell"></i> Klik slot penuh di atas terlebih dahulu, lalu klik tombol ini.
                </div>`;
        }
    } else {
        wlBox.style.display = 'none';
    }
}

function selectSlot(el, id) {
    document.querySelectorAll('.rec-slot').forEach(s => s.classList.remove('selected'));
    el.classList.add('selected');
    document.getElementById('selectedSlot').value = id;
    checkSubmitReady();

    fetch(`/bookings/check-conflict?slot_id=${id}`)
        .then(r => r.json())
        .then(data => {
            const zone  = document.getElementById('conflictZone');
            const items = document.getElementById('conflictItems');
            zone.style.display = 'block';
            let html = '';
            data.checks.forEach(c => {
                const cls  = c.ok ? 'conflict-ok' : 'conflict-err';
                const dot  = c.ok ? 'var(--success)' : 'var(--danger)';
                const icon = c.ok ? 'bi-check-circle-fill' : 'bi-x-circle-fill';
                html += `<div class="conflict-item ${cls}"><div class="conflict-dot" style="background:${dot}"></div><div><i class="bi ${icon} me-1"></i>${c.message}</div></div>`;
            });
            items.innerHTML = html;

            const btn = document.getElementById('submitBtn');
            if (data.has_conflict) {
                btn.disabled = true;
                btn.className = 'btn btn-danger w-100';
                btn.innerHTML = '<i class="bi bi-shield-x"></i> Ada Konflik — Pilih Slot Lain';
            } else {
                checkSubmitReady();
                btn.className = 'btn btn-primary w-100';
                btn.innerHTML = '<i class="bi bi-calendar-check-fill"></i> Konfirmasi Booking';
            }
        });
}

function selectWaitingSlot(id, label, jam) {
    document.getElementById('wlSlotId').value = id;
    const sel = document.getElementById('wlSelected');
    if (sel) {
        sel.style.display = 'block';
        document.getElementById('wlSelectedLabel').textContent = label + ' · ' + jam;
    }
    const btn = document.getElementById('wlBtn');
    if (btn) btn.disabled = false;
}

function submitQueue() {
    const topik = document.getElementById('topikInput').value;
    if (!topik) { alert('Pilih topik konsultasi terlebih dahulu!'); return; }
    if (!document.getElementById('wlSlotId').value) { alert('Pilih slot yang ingin dimasuki antreannya!'); return; }

    document.getElementById('wlTopik').value    = topik;
    document.getElementById('wlDeskripsi').value = document.getElementById('deskripsiInput').value;
    document.getElementById('queueForm').submit();
}
</script>
@endpush
