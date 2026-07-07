<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PeriodeAkademik extends Model
{
    use HasFactory;
    protected $table = 'periode_akademik';

    protected $fillable = [
        'nama_periode',
        'batas_waktu_input_nilai',
        'is_active'
    ];

    // Data Casting
    protected function casts(): array
    {
        return [
            'batas_waktu_input_nilai' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    // deklarasi relasi
    // relasi ke tabel kelas_mq
    public function kelasMq()
    {
        return $this->hasMany(KelasMq::class, 'periode_id');
    }
}
