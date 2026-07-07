<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TesPenempatan extends Model
{
    use HasFactory;
    protected $table = 'tes_penempatan';

    protected $fillable = [
        'mahasiswa_id',
        'admin_id',
        'nilai_tes',
        'hasil_tingkat'
    ];

    // data casting
    protected function casts(): array
    {
        return [
            'nilai_tes' => 'integer',
        ];
    }

    // deklarasi relasi
    // relasi ke tabel mahasiswa
    public function mahasiswa()
    {
        return $this->belongsTo(Mahasiswa::class, 'mahasiswa_id');
    }

    // relasi ke tabel admin
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
