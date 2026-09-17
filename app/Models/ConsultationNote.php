<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ConsultationNote extends Model
{
    protected $fillable = ['booking_id','dosen_id','catatan','tindak_lanjut','jadwal_berikutnya','status_lanjut'];
    protected $casts    = ['jadwal_berikutnya' => 'date'];

    public function booking() { return $this->belongsTo(Booking::class); }
    public function dosen()   { return $this->belongsTo(User::class,'dosen_id'); }
}
