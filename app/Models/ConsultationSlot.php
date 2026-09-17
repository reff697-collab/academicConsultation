<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConsultationSlot extends Model
{
    protected $fillable = ['dosen_id','tanggal','jam_mulai','jam_selesai','kapasitas','status','lokasi'];

    public function dosen()    { return $this->belongsTo(User::class,'dosen_id'); }
    public function bookings() { return $this->hasMany(Booking::class,'slot_id'); }
}
