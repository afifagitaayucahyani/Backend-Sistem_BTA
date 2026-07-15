<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\TesPenempatan;
use App\Models\Mahasiswa;

class TesPenempatanController extends Controller
{
    public function index()
    {
        // 
    }

    public function getBelumTes()
    {
        try {
            // 1. Ambil mahasiswa yang TIDAK PUNYA relasi di tabel tes_penempatan
            // 2. Gunakan with('user') agar nama mahasiswa ikut ditarik (mencegah N+1 Query Problem)
            $mahasiswaBelumTes = Mahasiswa::with('user')
                                          ->doesntHave('tesPenempatan')
                                          ->get();

            // 3. (Opsional tapi Direkomendasikan) Format ulang data agar lebih rapi saat diterima React
            $formattedData = $mahasiswaBelumTes->map(function ($mhs) {
                return [
                    'id'            => $mhs->id,           // ID dari tabel mahasiswa (penting untuk parameter simpan)
                    'nim'           => $mhs->nim,
                    'nama'          => $mhs->user ? $mhs->user->name : 'Nama tidak ditemukan', 
                    'program_studi' => $mhs->program_studi,
                    'fakultas'      => $mhs->fakultas,
                ];
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Data mahasiswa yang belum tes berhasil diambil',
                'data'    => $formattedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function inputNilai(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'mahasiswa_id' => 'required|exists:mahasiswa,id',
            'nilai_tes'    => 'required|integer|min:50|max:100', 
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // Proteksi Ganda: Cek apakah mahasiswa ini sudah pernah diinput nilainya
        $cekTes = TesPenempatan::where('mahasiswa_id', $request->mahasiswa_id)->first();
        if ($cekTes) {
            return response()->json([
                'message' => 'Mahasiswa ini sudah memiliki nilai tes penempatan di sistem.'
            ], 400);
        }

        // penentuan tingkat kelas
        $hasilTingkat = $request->nilai_tes >= 50 ? 'Mahir' : 'Menegah';
        // Simpan ke database
        $tes = TesPenempatan::create([
            'mahasiswa_id'  => $request->mahasiswa_id,
            'admin_id'      => $request->user()->id, 
            'nilai_tes'     => $request->nilai_tes,
            'hasil_tingkat' => $hasilTingkat,
        ]);

        return response()->json([
            'message' => 'Nilai tes berhasil disimpan. Mahasiswa ditempatkan di tingkat: ' . $hasilTingkat,
            'data'    => $tes
        ], 201);
    }
}
