<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KelasMq extends Model
{
    use HasFactory;
    protected $table = 'kelas_mq';

    protected $fillable = [
        'periode_id',
        'tutor_id',
        'tingkat',
        'kapasitas_jumlah',
    ];

    // Data Casting 
    protected function casts(): array
    {
        return [
            // memeastikan data selalu berupa angka integer
            'kapasitas_jumlah' => 'integer',
        ];
    }

    // deklarasi relasi
    // relasi ke tabel periode_akademik
    public function periode()
    {
        return $this->belongsTo(PeriodeAkademik::class, 'periode_id');
    }

    // relasi ke tabel users (tutor)
    public function tutor()
    {
        return $this->belongsTo(User::class, 'tutor_id');
    }

    // relasi ke peserta kelas
    public function pesertaKelas()
    {
        return $this->hasMany(PesertaKelas::class, 'kelas_id');
    }

    // relasi ke tabel absensi
    public function absensi()
    {
        return $this->hasMany(Absensi::class, 'kelas_id');
    }

    // relasi ke tabel nilai akhir
    public function nilaiAkhir()
    {
        return $this->hasMany(NilaiAkhir::class, 'kelas_id');
    }
}
