<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mahasiswa extends Model
{
    use HasFactory;
    protected $table = 'mahasiswa';

    protected $fillable = [
        'user_id',
        'nim',
        'program_studi',
        'fakultas',
    ];

    // deklarasi relasi
    // relasi ke tabel user
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // relasi ke tabel pendaftaran BTA
    public function pendaftaranBTA()
    {
        return $this->hasMany(PendaftaranBta::class);
    }

    // relasi ke tabel nilai akhir
    public function nilaiAkhir()
    {
        return $this->hasMany(NilaiAkhir::class);
    }

    // relasi ke tabel sertifikat
    public function sertifikat()
    {
        return $this->hasOne(Sertifikat::class);
    }


}
