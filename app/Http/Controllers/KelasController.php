<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\KelasMq;
use App\Models\PesertaKelas;
use App\Models\User;

class KelasController extends Controller
{
    // kelas
    // aktor admin dan kepala pusat
    public function index()
    {{
        // Menarik semua data kelas beserta nama tutor yang mengajar
        $kelas = KelasMq::with('tutor:id,name,email')->latest()->get();

        return response()->json([
            'message' => 'Daftar semua kelas berhasil diambil.',
            'data'    => $kelas
        ], 200);
    }}

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_kelas' => 'required|string|max:100', 
            'tingkat'    => 'required|in:Menegah,Mahir', 
            'kapasitas_jumlah'=> 'required|integer|min:1|max:50',
            'periode_id' => 'required|exists:periode_akademik,id',
            'jadwal'     => 'nullable|string' 
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $kelas = KelasMq::create([
            'nama_kelas' => $request->nama_kelas,
            'tingkat'    => $request->tingkat,
            'kapasitas_jumlah'=> $request->kapasitas_jumlah,
            'periode_id' => $request->periode_id,
            'jadwal'     => $request->jadwal,
            
        ]);

        return response()->json([
            'message' => 'Kelas baru berhasil dibuat.',
            'data'    => $kelas
        ], 201);
    }

    public function plotTutor(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'tutor_id' => 'required|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Cek keamanan ganda: Pastikan user yang dipilih benar-benar memiliki role 'Tutor'
        $calonTutor = User::find($request->tutor_id);
        if (!$calonTutor->hasRole('Tutor')) {
            return response()->json([
                'message' => 'Pengguna yang dipilih bukan seorang Tutor.'
            ], 403);
        }

        $kelas = KelasMq::find($id);
        if (!$kelas) {
            return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
        }

        // Masukkan ID Tutor ke kelas tersebut
        $kelas->tutor_id = $calonTutor->id;
        $kelas->save();

        return response()->json([
            'message' => 'Tutor ' . $calonTutor->name . ' berhasil ditugaskan ke ' . $kelas->nama_kelas,
            'data'    => $kelas->load('tutor:id,name') 
        ], 200);
    }

    // aktor tutor
    public function kelasKu(Request $request)
    {
        // Hanya mengambil kelas yang 'tutor_id'-nya sama dengan ID Tutor yang sedang login
        $kelasTutor = KelasMq::where('tutor_id', $request->user()->id)
            ->latest()
            ->get();

        return response()->json([
            'message' => 'Daftar kelas Anda berhasil diambil.',
            'data'    => $kelasTutor
        ], 200);
    }

    // aktor staff dan tutor
    public function detailKelas($id)
    {
        $kelas = KelasMq::with('tutor:id,name')->find($id);

        if (!$kelas) {
            return response()->json(['message' => 'Kelas tidak ditemukan.'], 404);
        }

        // Mengambil daftar mahasiswa yang tergabung di kelas ini menggunakan Tabel Pivot PesertaKelas
        $peserta = PesertaKelas::where('kelas_id', $id)
            ->with('mahasiswa.user:id,name,email') 
            ->get();

        return response()->json([
            'message' => 'Detail kelas berhasil diambil.',
            'data'    => [
                'info_kelas' => $kelas,
                'total_peserta' => $peserta->count(),
                'daftar_mahasiswa' => $peserta
            ]
        ], 200);
    }

    public function tambahPeserta(Request $request, $id)
{
    // 1. Validasi input harus berupa array
    $request->validate([
        'mahasiswa_id'   => 'required|array',
        'mahasiswa_id.*' => 'exists:mahasiswa,id' // Memastikan semua ID valid
    ]);

    // 2. Cari kelasnya
    $kelas = KelasMq::findOrFail($id);

    // 3. Hitung jumlah peserta saat ini menggunakan Model PesertaKelas
    $jumlahPesertaSaatIni = PesertaKelas::where('kelas_id', $id)->count();
    $jumlahPesertaBaru = count($request->mahasiswa_id);

    // Cek sisa kapasitas
    if (($jumlahPesertaSaatIni + $jumlahPesertaBaru) > $kelas->kapasitas_jumlah) {
        return response()->json([
            'message' => 'Gagal! Kapasitas kelas tidak mencukupi.',
            'sisa_kuota' => $kelas->kapasitas_jumlah - $jumlahPesertaSaatIni
        ], 400);
    }

    // 4. Filter data duplikat (Mencegah mahasiswa yang sama masuk 2x ke kelas ini)
    $mahasiswaSudahAda = PesertaKelas::where('kelas_id', $id)
        ->whereIn('mahasiswa_id', $request->mahasiswa_id)
        ->pluck('mahasiswa_id')
        ->toArray();

    // Pisahkan ID mahasiswa yang benar-benar baru (belum ada di kelas ini)
    $mahasiswaBaru = array_diff($request->mahasiswa_id, $mahasiswaSudahAda);

    // Jika ternyata semua ID yang dikirim Admin sudah ada di kelas
    if (empty($mahasiswaBaru)) {
        return response()->json([
            'message' => 'Gagal ditambahkan. Semua mahasiswa yang dipilih sudah terdaftar di kelas ini.'
        ], 422);
    }

    // 5. Siapkan array untuk Bulk Insert (agar lebih cepat dan ringan di database)
    $dataInsert = [];
    foreach ($mahasiswaBaru as $mhs_id) {
        $dataInsert[] = [
            'kelas_id'     => $id,
            'mahasiswa_id' => $mhs_id,
            'created_at'   => now(),
            'updated_at'   => now(),
        ];
    }

    // Eksekusi simpan ke database melalui Model PesertaKelas
    PesertaKelas::insert($dataInsert);

    // 6. Kembalikan response yang informatif
    return response()->json([
        'message' => count($mahasiswaBaru) . ' mahasiswa baru berhasil dimasukkan ke dalam kelas.',
        'total_peserta_sekarang' => PesertaKelas::where('kelas_id', $id)->count(),
        'mahasiswa duplikat' => count($mahasiswaSudahAda) 
    ], 201);
}
}
