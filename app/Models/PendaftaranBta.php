<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PendaftaranBta extends Model
{
   use HasFactory;
    protected $table = 'pendaftaran_bta';

    protected $fillable = [
        'mahasiswa_id',
        'file_slip_pembayaran',
        'status_validasi'
    ];

    // deklarasi relasi
    // relasi ke tabel mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }
}
