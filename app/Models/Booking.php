<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = ['kode_booking','mahasiswa_id','dosen_id','slot_id','topik','deskripsi','status','alasan_batal','confirmed_at'];
    protected $casts    = ['confirmed_at' => 'datetime'];

    public function mahasiswa() { return $this->belongsTo(User::class,'mahasiswa_id'); }
    public function dosen()     { return $this->belongsTo(User::class,'dosen_id'); }
    public function slot()      { return $this->belongsTo(ConsultationSlot::class,'slot_id'); }
    public function note()      { return $this->hasOne(ConsultationNote::class,'booking_id'); }
}
