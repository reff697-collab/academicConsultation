<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waiting_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mahasiswa_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('slot_id')->constrained('consultation_slots')->onDelete('cascade');
            $table->enum('topik', [
                'Bimbingan Akademik',
                'Tugas Akhir',
                'Revisi Tugas',
                'Konsultasi Nilai',
                'Magang/KP',
                'Lainnya'
            ]);
            $table->text('deskripsi')->nullable();
            $table->integer('posisi')->default(1);
            $table->enum('status', ['menunggu', 'diproses', 'batal'])->default('menunggu');
            $table->timestamps();
        });

        Schema::create('consultation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('dosen_id')->constrained('users')->onDelete('cascade');
            $table->text('catatan');
            $table->text('tindak_lanjut')->nullable();
            $table->date('jadwal_berikutnya')->nullable();
            $table->enum('status_lanjut', ['selesai', 'perlu_lanjut', 'ditunda'])->default('selesai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waiting_lists');
        Schema::dropIfExists('consultation_notes');
    }
};
