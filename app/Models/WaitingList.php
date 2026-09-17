<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class WaitingList extends Model
{
    protected $fillable = ['mahasiswa_id','dosen_id','slot_id','topik','deskripsi','posisi','status'];

    public function mahasiswa() { return $this->belongsTo(User::class,'mahasiswa_id'); }
    public function dosen()     { return $this->belongsTo(User::class,'dosen_id'); }
    public function slot()      { return $this->belongsTo(ConsultationSlot::class,'slot_id'); }
}
