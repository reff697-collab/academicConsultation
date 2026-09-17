<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('kode_booking')->unique();
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
            $table->enum('status', [
                'menunggu',
                'dikonfirmasi',
                'selesai',
                'dibatalkan',
                'tidak_hadir'
            ])->default('menunggu');
            $table->text('alasan_batal')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
