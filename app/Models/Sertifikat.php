<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sertifikat extends Model
{
    protected $table = 'sertifikat';

    protected $fillable = [
        'mahasiswa_id',
        'nomor_sk',
        'keterangan_nilai_total',
        'keterangan_huruf_mutu',
        'file_sertifikat',
    ];

    // deklarasi relasi
    // relasi ke tabel mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
