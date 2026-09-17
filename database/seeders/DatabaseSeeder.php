<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ===================== USERS =====================
        $users = [
            // Admin
            ['name' => 'Administrator', 'email' => 'admin@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'admin', 'nim_nidn' => 'ADM001', 'jurusan' => 'Teknik Informatika', 'angkatan' => null],
            // Dosen
            ['name' => 'Dr. Rina Sari, M.Kom', 'email' => 'rina@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'dosen', 'nim_nidn' => '0101197001', 'jurusan' => 'Teknik Informatika', 'angkatan' => null],
            ['name' => 'Dr. Andi Wijaya, M.T', 'email' => 'andi@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'dosen', 'nim_nidn' => '0205196802', 'jurusan' => 'Teknik Informatika', 'angkatan' => null],
            ['name' => 'Dr. Hendra Gunawan, M.Si', 'email' => 'hendra@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'dosen', 'nim_nidn' => '0312197503', 'jurusan' => 'Sistem Informasi', 'angkatan' => null],
            ['name' => 'Prof. Dewi Kurnia, Ph.D', 'email' => 'dewi@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'dosen', 'nim_nidn' => '0718196804', 'jurusan' => 'Teknik Informatika', 'angkatan' => null],
            // Mahasiswa
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'mahasiswa', 'nim_nidn' => '2021001', 'jurusan' => 'Teknik Informatika', 'angkatan' => '2021'],
            ['name' => 'Siti Rahma', 'email' => 'siti@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'mahasiswa', 'nim_nidn' => '2021002', 'jurusan' => 'Teknik Informatika', 'angkatan' => '2021'],
            ['name' => 'Budi Pratama', 'email' => 'budi@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'mahasiswa', 'nim_nidn' => '2021003', 'jurusan' => 'Sistem Informasi', 'angkatan' => '2021'],
            ['name' => 'Dewi Nurhaliza', 'email' => 'dewin@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'mahasiswa', 'nim_nidn' => '2021004', 'jurusan' => 'Teknik Informatika', 'angkatan' => '2021'],
            ['name' => 'Rizky Firmansyah', 'email' => 'rizky@educonsult.ac.id', 'password' => Hash::make('password'), 'role' => 'mahasiswa', 'nim_nidn' => '2022001', 'jurusan' => 'Teknik Informatika', 'angkatan' => '2022'],
        ];
        DB::table('users')->insert($users);

        // ===================== SLOTS =====================
        $dosenIds = [2, 3, 4, 5]; // Dr. Rina, Dr. Andi, Dr. Hendra, Prof. Dewi
        $locations = ['Ruang Dosen Lt. 2', 'Ruang Dosen Lt. 3', 'Online (Zoom)', 'Ruang Meeting'];
        $slots = [];
        $now = Carbon::now();

        foreach ($dosenIds as $idx => $dosenId) {
            for ($w = -2; $w <= 2; $w++) {
                for ($d = 1; $d <= 5; $d++) {
                    $date = $now->copy()->startOfWeek()->addWeeks($w)->addDays($d - 1);
                    $times = [['08:00','09:00'],['09:00','10:00'],['10:00','11:00'],['13:00','14:00'],['14:00','15:00'],['15:00','16:00']];
                    foreach ($times as $t) {
                        $slots[] = [
                            'dosen_id' => $dosenId,
                            'tanggal' => $date->format('Y-m-d'),
                            'jam_mulai' => $t[0],
                            'jam_selesai' => $t[1],
                            'kapasitas' => 1,
                            'status' => 'tersedia',
                            'lokasi' => $locations[$idx],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }
        DB::table('consultation_slots')->insert($slots);

        // ===================== BOOKINGS (history data) =====================
        $topikList = ['Tugas Akhir', 'Bimbingan Akademik', 'Revisi Tugas', 'Konsultasi Nilai', 'Magang/KP', 'Lainnya'];
        $statusList = ['selesai', 'selesai', 'selesai', 'dikonfirmasi', 'dibatalkan'];
        $mhsIds = [6, 7, 8, 9, 10];
        $bookings = [];
        $kode = 1;

        // Past bookings for history & stats
        for ($i = 0; $i < 30; $i++) {
            $mhsId = $mhsIds[array_rand($mhsIds)];
            $dosenId = $dosenIds[array_rand($dosenIds)];
            $slotRow = DB::table('consultation_slots')
                ->where('dosen_id', $dosenId)
                ->where('tanggal', '<', now()->format('Y-m-d'))
                ->inRandomOrder()->first();
            if (!$slotRow) continue;
            $status = $statusList[array_rand($statusList)];
            $bookings[] = [
                'kode_booking' => 'BK' . str_pad($kode++, 4, '0', STR_PAD_LEFT),
                'mahasiswa_id' => $mhsId,
                'dosen_id' => $dosenId,
                'slot_id' => $slotRow->id,
                'topik' => $topikList[array_rand($topikList)],
                'deskripsi' => 'Konsultasi mengenai ' . strtolower($topikList[array_rand($topikList)]),
                'status' => $status,
                'alasan_batal' => $status === 'dibatalkan' ? 'Konflik jadwal mendadak' : null,
                'confirmed_at' => $status !== 'menunggu' ? now()->subDays(rand(1, 30)) : null,
                'created_at' => now()->subDays(rand(1, 45)),
                'updated_at' => now()->subDays(rand(0, 10)),
            ];
        }

        // Upcoming bookings (Ahmad Fauzi = user 6)
        $upcomingSlot = DB::table('consultation_slots')
            ->where('dosen_id', 2)->where('tanggal', '>=', now()->format('Y-m-d'))
            ->orderBy('tanggal')->first();
        if ($upcomingSlot) {
            $bookings[] = [
                'kode_booking' => 'BK' . str_pad($kode++, 4, '0', STR_PAD_LEFT),
                'mahasiswa_id' => 6,
                'dosen_id' => 2,
                'slot_id' => $upcomingSlot->id,
                'topik' => 'Tugas Akhir',
                'deskripsi' => 'Konsultasi perkembangan BAB I dan BAB II skripsi',
                'status' => 'dikonfirmasi',
                'alasan_batal' => null,
                'confirmed_at' => now()->subDays(1),
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(1),
            ];
        }

        DB::table('bookings')->insert($bookings);

        // ===================== CONSULTATION NOTES =====================
        $selesaiBookings = DB::table('bookings')->where('status', 'selesai')->get();
        $notes = [];
        $catatanSamples = [
            ['Latar belakang sudah bagus, perbaiki rumusan masalah agar lebih spesifik. Tambahkan referensi jurnal internasional minimal 5 di BAB II.', 'Revisi BAB I dan BAB II, konsultasi ulang dalam 2 minggu.', 'selesai'],
            ['Pemahaman materi sudah cukup baik. Tingkatkan partisipasi di kelas dan aktif bertanya.', 'Pelajari kembali modul bab 4-6 sebelum UAS.', 'perlu_lanjut'],
            ['Proposal magang sudah sesuai format. Lengkapi dokumen administrasi sebelum pengajuan.', 'Segera hubungi koordinator magang untuk proses selanjutnya.', 'selesai'],
            ['Nilai UTS di bawah rata-rata, perlu lebih aktif mengerjakan tugas tambahan.', 'Ikuti bimbingan belajar dan diskusi kelompok secara rutin.', 'perlu_lanjut'],
            ['Revisi tugas sudah bagus, tinggal perbaiki format penulisan daftar pustaka.', null, 'selesai'],
        ];
        foreach ($selesaiBookings->take(15) as $b) {
            $sample = $catatanSamples[array_rand($catatanSamples)];
            $notes[] = [
                'booking_id' => $b->id,
                'dosen_id' => $b->dosen_id,
                'catatan' => $sample[0],
                'tindak_lanjut' => $sample[1],
                'jadwal_berikutnya' => $sample[1] ? now()->addDays(rand(7, 21))->format('Y-m-d') : null,
                'status_lanjut' => $sample[2],
                'created_at' => now()->subDays(rand(1, 20)),
                'updated_at' => now()->subDays(rand(0, 5)),
            ];
        }
        DB::table('consultation_notes')->insert($notes);

        // ===================== WAITING LIST =====================
        $futureSlot = DB::table('consultation_slots')
            ->where('dosen_id', 2)->where('tanggal', '>=', now()->format('Y-m-d'))
            ->orderBy('tanggal')->skip(1)->first();
        if ($futureSlot) {
            DB::table('waiting_lists')->insert([
                ['mahasiswa_id' => 7, 'dosen_id' => 2, 'slot_id' => $futureSlot->id, 'topik' => 'Revisi Tugas', 'deskripsi' => 'Minta review revisi tugas besar', 'posisi' => 1, 'status' => 'menunggu', 'created_at' => now(), 'updated_at' => now()],
                ['mahasiswa_id' => 8, 'dosen_id' => 2, 'slot_id' => $futureSlot->id, 'topik' => 'Bimbingan Akademik', 'deskripsi' => 'Konsultasi rencana studi semester depan', 'posisi' => 2, 'status' => 'menunggu', 'created_at' => now(), 'updated_at' => now()],
            ]);
        }
    }
}
