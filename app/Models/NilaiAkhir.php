<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NilaiAkhir extends Model
{
    use HasFactory;
    protected $table = 'nilai_akhir';

    protected $fillable = [
        'kelas_id',
        'mahasiswa_id',
        'nilai_total',
        'huruf_mutu',
        'status_validasi',
        'status_kelulusan',
    ];

    // data casting
    protected function casts(): array
    {
        return [

            'nilai_total' => 'integer',

            'status_validasi' => 'boolean',
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
}
