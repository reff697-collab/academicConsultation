<?php
// ==============================
// User.php
// ==============================
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    protected $fillable = ['name','email','password','role','nim_nidn','jurusan','angkatan','photo'];
    protected $hidden   = ['password','remember_token'];
    protected $casts    = ['password' => 'hashed'];

    public function bookingsMhs()   { return $this->hasMany(Booking::class,'mahasiswa_id'); }
    public function bookingsDosen() { return $this->hasMany(Booking::class,'dosen_id'); }
    public function slots()         { return $this->hasMany(ConsultationSlot::class,'dosen_id'); }
    public function notesDosen()    { return $this->hasMany(ConsultationNote::class,'dosen_id'); }
    public function waitingLists()  { return $this->hasMany(WaitingList::class,'mahasiswa_id'); }
}
