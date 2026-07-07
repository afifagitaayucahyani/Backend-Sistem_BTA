<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Absensi extends Model
{
   use HasFactory;
   protected $table = 'absensi';

   protected $fillable = [
        'kelas_id',
        'mahasiswa_id',
        'tutor_id',
        'tanggal_absensi',
        'status_absensi',
        'is_valid',
   ];

    // Data casting (Mengubah tipe data otomatis saat diambil dari database)
    protected Function casts(): array
    {
        return [
            'tanggal_absensi' => 'datetime',
            'is_valid' => 'boolean',
        ];
    }

    // deklarasi relasi
    // relasi ke tabel kelas_mq
    public function kelas()
    {
        return $this->belongsTo(KelasMq::class, 'kelas_id');
    }

    // relasi ke tabel mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    // relasi ke tabel users (tutor)
    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }
}
